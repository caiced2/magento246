<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Observer\AddPaymentGiftWrappingItem;
use Magento\Payment\Model\Cart;
use Magento\Payment\Model\Cart\SalesModel\SalesModelInterface;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for adding gift wrapping items into payment checkout
 */
class AddPaymentGiftWrappingItemTest extends TestCase
{
    /** @var AddPaymentGiftWrappingItem */
    protected $_model;

    /**
     * @var Observer
     */
    protected $_observer;

    /**
     * @var DataObject
     */
    protected $_event;

    /**
     * @var MockObject
     */
    protected $helperDataMock;

    /**
     * @var MockObject
     */
    protected $observerMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->_model = $objectManagerHelper->getObject(
            AddPaymentGiftWrappingItem::class
        );
        $this->_event = new DataObject();
        $this->_observer = new Observer(['event' => $this->_event]);
    }

    /**
     * @param float $amount
     * @dataProvider addPaymentGiftWrappingItemTotalCardDataProvider
     */
    public function testAddPaymentGiftWrappingItemTotalCard($amount)
    {
        $salesModel = $this->getMockForAbstractClass(SalesModelInterface::class);
        $salesModel->expects($this->once())->method('getAllItems')->willReturn([]);
        $salesModel->expects($this->any())->method('getDataUsingMethod')->willReturnCallback(
            function ($key) use ($amount) {
                if ($key == 'gw_card_base_price') {
                    return $amount;
                } elseif ($key == 'gw_add_card' && is_float($amount)) {
                    return true;
                } else {
                    return null;
                }
            }
        );
        $cart = $this->createMock(Cart::class);
        $cart->expects($this->any())->method('getSalesModel')->willReturn($salesModel);
        if ($amount) {
            $cart->expects($this->once())->method('addCustomItem')->with(__('Printed Card'), 1, $amount);
        } else {
            $cart->expects($this->never())->method('addCustomItem');
        }
        $this->_event->setCart($cart);
        $this->_model->execute($this->_observer);
    }

    public function addPaymentGiftWrappingItemTotalCardDataProvider()
    {
        return [[null], [0], [0.12]];
    }

    /**
     * Tests all possible variations of carts with and without gift wrapping
     *
     * @param array $items
     * @param array $salesModelData
     * @param float $expected
     * @dataProvider addPaymentGiftWrappingItemTotalWrappingDataProvider
     */
    public function testAddPaymentGiftWrappingItemTotalWrapping(array $items, array $salesModelData, float $expected)
    {
        $salesModel = $this->getMockForAbstractClass(SalesModelInterface::class);
        $salesModelData = new DataObject($salesModelData);
        $salesModel->expects($this->once())
            ->method('getAllItems')
            ->willReturn($items);
        $salesModel->expects($this->any())
            ->method('getDataUsingMethod')
            ->willReturnCallback([$salesModelData, 'getDataUsingMethod']);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->any())->method('getSalesModel')->willReturn($salesModel);
        if ($expected) {
            $cart->expects($this->once())->method('addCustomItem')->with(__('Gift Wrapping'), 1, $expected);
        } else {
            $cart->expects($this->never())->method('addCustomItem');
        }
        $this->_event->setCart($cart);
        $this->_model->execute($this->_observer);
    }

    /**
     * @return array
     */
    public function addPaymentGiftWrappingItemTotalWrappingDataProvider()
    {
        $data = [];

        $qtyAttributeVariations = [
            // use case: quote
            Item::class => 'qty',
            // use case: order
            \Magento\Sales\Model\Order\Item::class => 'qty_ordered',
        ];

        foreach ($qtyAttributeVariations as $contract => $qtyAttribute) {
            $originalItems = [
                ['gw_id' => 1, 'gw_base_price' => 0.3, $qtyAttribute => 1, 'parent_item' => true],
                ['gw_id' => null, 'gw_base_price' => 0.3, $qtyAttribute => 1],
                ['gw_id' => 1, 'gw_base_price' => 0.0, $qtyAttribute => 1],
                ['gw_id' => 2, 'gw_base_price' => null, $qtyAttribute => 1],
                ['gw_id' => 3, 'gw_base_price' => 0.12, $qtyAttribute => 1],
                ['gw_id' => 4, 'gw_base_price' => 2.1, $qtyAttribute => 1],
                ['gw_id' => 5, 'gw_base_price' => 1, $qtyAttribute => 2],
            ];

            $items = [];

            foreach ($originalItems as $originalItemData) {
                $mock = $this->createPartialMock($contract, []);
                $mock->setData($originalItemData);
                if (isset($originalItemData['parent_item'])) {
                    $mock->setParentItem($this->createPartialMock($contract, []));
                }
                $items[] = new DataObject(['original_item' =>  $mock]);
            }

            $salesModelDataVariations = [
                ['gw_id' => 1, 'gw_base_price' => null],
                ['gw_id' => 1, 'gw_base_price' => 0],
                ['gw_id' => 1, 'gw_base_price' => 0.12],
            ];

            foreach ($salesModelDataVariations as $salesModelData) {
                // cart with no items: 0
                $data[] = [[], $salesModelData, 0 + (float) $salesModelData['gw_base_price']];
                // cart with items: 4.22 = 1 * 0.12 + 1 * 2.1 + 2 * 1
                $data[] = [$items, $salesModelData, 4.22 + (float) $salesModelData['gw_base_price']];
            }
        }

        return $data;
    }
}
