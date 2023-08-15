<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Observer\AddPaymentGiftCardItem;
use Magento\Payment\Model\Cart;
use Magento\Payment\Model\Cart\SalesModel\SalesModelInterface;
use PHPUnit\Framework\TestCase;

class AddPaymentGiftCardItemTest extends TestCase
{
    /**
     * @var DataObject
     */
    private $event;

    /** @var AddPaymentGiftCardItem */
    private $model;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var SalesModelInterface
     */
    private $salesModelMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(AddPaymentGiftCardItem::class);
        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);
        $this->salesModelMock = $this->getMockForAbstractClass(SalesModelInterface::class);
    }

    /**
     * @param float|string $amount
     * @dataProvider addPaymentGiftCardItemDataProviderSuccess
     */
    public function testAddPaymentGiftCardItemSuccess($amount)
    {
        $this->salesModelMock->expects(
            $this->once()
        )->method(
            'getDataUsingMethod'
        )->with(
            'base_gift_cards_amount'
        )->willReturn(
            $amount
        );
        $cartMock = $this->createMock(Cart::class);
        $cartMock->expects($this->once())->method('getSalesModel')->willReturn($this->salesModelMock);
        $cartMock->expects($this->once())->method('addDiscount')->with(abs((float)$amount));
        $this->event->setCart($cartMock);
        $this->model->execute($this->observer);
    }

    /**
     * @param float|string $amount
     * @dataProvider addPaymentGiftCardItemDataProviderFail
     */
    public function testAddPaymentGiftCardItemFail($amount)
    {
        $this->salesModelMock->expects(
            $this->once()
        )->method(
            'getDataUsingMethod'
        )->with(
            'base_gift_cards_amount'
        )->willReturn(
            $amount
        );
        $cartMock = $this->createMock(Cart::class);
        $cartMock->expects($this->once())->method('getSalesModel')->willReturn($this->salesModelMock);
        $cartMock->expects($this->never())->method('addDiscount');
        $this->event->setCart($cartMock);
        $this->model->execute($this->observer);
    }

    /**
     * @return array
     */
    public function addPaymentGiftCardItemDataProviderSuccess()
    {
        return [[0.1], [-0.1], ['0.1']];
    }

    /**
     * @return array
     */
    public function addPaymentGiftCardItemDataProviderFail()
    {
        return [[0.0], [''], [' ']];
    }
}
