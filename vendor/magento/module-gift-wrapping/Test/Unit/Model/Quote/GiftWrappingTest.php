<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Quote;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\GiftWrapping\Helper\Data;
use Magento\GiftWrapping\Model\Total\Quote\Giftwrapping;
use Magento\GiftWrapping\Model\Wrapping;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\GiftWrapping\Model\Quote\Giftwrapping
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftWrappingTest extends TestCase
{
    /**
     * @var MockObject|Wrapping
     */
    protected $wrappingMock;

    /**
     * @var MockObject|Address
     */
    protected $addressMock;

    /**
     * @var MockObject|PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Test for collect method
     *
     * @param bool $withProduct
     * @dataProvider collectQuoteDataProvider
     */
    public function testCollectQuote($withProduct)
    {
        $shippingAssignmentMock = $this->_prepareData();
        $helperMock = $this->createMock(Data::class);
        $factoryMock = $this->createPartialMock(\Magento\GiftWrapping\Model\WrappingFactory::class, ['create']);
        $factoryMock->expects($this->any())->method('create')->willReturn($this->wrappingMock);

        $model = new Giftwrapping($helperMock, $factoryMock, $this->priceCurrency);
        $item = new DataObject();

        $product = $this->createPartialMock(Product::class, ['isVirtual']);
        $product->expects($this->any())->method('isVirtual')->willReturn(false);
        if ($withProduct) {
            $product->setGiftWrappingPrice(10);
        } else {
            $product->setGiftWrappingPrice(0);
            $item->setWrapping($this->wrappingMock);
        }
        $item->setProduct($product)->setQty(2)->setGwId(1);

        $storeMock = $this->createMock(Store::class);
        $shippingAssignmentMock->expects($this->any())->method('getItems')->willReturn([$item]);

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(
                [
                    'setGwItemsBasePrice',
                    'setGwItemsPrice',
                    'setGwBasePrice',
                    'setGwPrice',
                    'setGwCardBasePrice',
                    'setGwCardPrice',
                    'getGwItemsBasePrice',
                    'getGwItemsPrice',
                    'getGwBasePrice',
                    'getGwPrice',
                    'getGwCardBasePrice',
                    'getGwCardPrice'
                ]
            )
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->atLeastOnce())->method('setGwItemsBasePrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwItemsPrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwBasePrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwPrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwCardBasePrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('setGwCardPrice')->willReturnSelf();
        $quoteMock->expects($this->atLeastOnce())->method('getGwItemsBasePrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwItemsPrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwBasePrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwPrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwCardBasePrice');
        $quoteMock->expects($this->atLeastOnce())->method('getGwCardPrice');
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $totalMock = $this->getMockBuilder(Total::class)
            ->addMethods(
                [
                    'setBaseGrandTotal',
                    'getBaseGrandTotal',
                    'getGwItemsBasePrice',
                    'getGwBasePrice',
                    'getGwCardBasePrice',
                    'getGrandTotal',
                    'getGwItemsPrice',
                    'getGwPrice',
                    'getGwCardPrice',
                    'setGwItemsBasePrice',
                    'setGwItemsPrice',
                    'setGwItemIds',
                    'setGwCardBasePrice',
                    'setGwCardPrice',
                    'setGwAddCard'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $totalMock->expects($this->atLeastOnce())->method('setBaseGrandTotal');
        $totalMock->expects($this->atLeastOnce())->method('getBaseGrandTotal');
        $totalMock->expects($this->atLeastOnce())->method('getGwItemsBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('getGwBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('getGwCardBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('getGrandTotal');
        $totalMock->expects($this->atLeastOnce())->method('getGwItemsPrice');
        $totalMock->expects($this->atLeastOnce())->method('getGwPrice');
        $totalMock->expects($this->atLeastOnce())->method('getGwCardPrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwItemsBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwItemsPrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwItemIds');
        $totalMock->expects($this->atLeastOnce())->method('setGwCardBasePrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwCardPrice');
        $totalMock->expects($this->atLeastOnce())->method('setGwAddCard');

        $model->collect($quoteMock, $shippingAssignmentMock, $totalMock);
    }

    /**
     * Prepare mocks for test
     *
     * @return MockObject
     */
    protected function _prepareData()
    {
        $this->wrappingMock = $this->createPartialMock(
            Wrapping::class,
            ['load', 'setStoreId', 'getBasePrice']
        );
        $this->addressMock = $this->getMockBuilder(Address::class)
            ->addMethods(['getAddressType'])
            ->onlyMethods(['getQuote', 'getAllItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceCurrency = $this->getMockBuilder(
            PriceCurrencyInterface::class
        )->getMock();
        $this->priceCurrency->expects($this->any())->method('convert')->willReturn(10);

        $this->wrappingMock->expects($this->any())->method('load')->willReturnSelf();
        $this->wrappingMock->expects($this->any())->method('getBasePrice')->willReturn(6);
        $this->addressMock->expects($this->any())->method('getAddressType')->willReturn(Address::TYPE_SHIPPING);

        $shippingAssignmentMock = $this->getMockForAbstractClass(ShippingAssignmentInterface::class);

        $shippingMock = $this->getMockForAbstractClass(ShippingInterface::class);
        $shippingAssignmentMock->expects($this->once())->method('getShipping')->willReturn($shippingMock);
        $shippingMock->expects($this->once())->method('getAddress')->willReturn($this->addressMock);

        return $shippingAssignmentMock;
    }

    /**
     * Data provider for testCollectQuote
     *
     * @return array
     */
    public function collectQuoteDataProvider()
    {
        return [
            'withProduct' => [true],
            'withoutProduct' => [false]
        ];
    }
}
