<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Quote\Tax;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Helper\Data;
use Magento\GiftWrapping\Model\Total\Quote\Tax\Giftwrapping;
use Magento\GiftWrapping\Model\Wrapping;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\GiftWrapping\Model\Quote\Tax\Giftwrapping
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
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->wrappingMock = $this->createPartialMock(
            Wrapping::class,
            ['load', 'setStoreId', 'getBasePrice']
        );
    }

    /**
     * Test for collect method
     */
    public function testCollectQuote()
    {
        $helperMock = $this->createMock(Data::class);
        $helperMock->expects($this->any())->method('getWrappingTaxClass')->willReturn(2);
        $wrappingFactoryMock = $this->createPartialMock(\Magento\GiftWrapping\Model\WrappingFactory::class, ['create']);
        $wrappingFactoryMock->expects($this->once())->method('create')->willReturn($this->wrappingMock);
        $product = $this->getMockBuilder(Product::class)
            ->addMethods(['setGiftWrappingPrice'])
            ->onlyMethods(['isVirtual'])
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock = $this->getMockBuilder(Address::class)
            ->addMethods(
                [
                    'getAddressType',
                    'setGwItemsBaseTaxAmount',
                    'setGwItemsTaxAmount',
                    'getExtraTaxableDetails'
                ]
            )
            ->onlyMethods(['getCustomAttributesCodes'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->any())->method('isVirtual')->willReturn(false);
        $this->wrappingMock->expects($this->any())->method('load')->willReturnSelf();
        $this->wrappingMock->expects($this->any())->method('getBasePrice')->willReturn(6);
        $item = $this->getMockBuilder(Item::class)
            ->addMethods(['setAssociatedTaxables', 'getGwId'])
            ->onlyMethods(['setProduct', 'getProduct', 'getQty'])
            ->disableOriginalConstructor()
            ->getMock();
        $product->setGiftWrappingPrice(10);
        $item->expects($this->once())->method('getGwId')->willReturn(1);
        $item->expects($this->any())->method('getProduct')->willReturn($product);
        $item->expects($this->any())->method('getQty')->willReturn(2);
        $addressMock->expects($this->any())->method('getAddressType')->willReturn('shipping');
        $addressMock->expects($this->any())->method('getCustomAttributesCodes')->willReturn([]);
        $expected = [
            [
                'type' => 'item_gw',
                'code' => 'item_gw1',
                'unit_price' => 10,
                'base_unit_price' => 6,
                'quantity' => 2,
                'tax_class_id' => 2,
                'price_includes_tax' => false,
            ],
        ];
        $item->expects($this->once())->method('setAssociatedTaxables')->with($expected);
        $storeMock = $this->getMockBuilder(Store::class)
            ->addMethods(['convertPrice'])
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())->method('convertPrice')->willReturn(10);

        $quoteData = [
            'isMultishipping' => false,
            'store' => $storeMock,
            'billingAddress' => null,
            'customerTaxClassId' => null,
            'tax_class_id' => 2,
        ];
        $quote = $this->createMock(Quote::class);
        $quote->expects($this->any())->method('setData')->with($quoteData)->willReturnSelf();
        $quote->expects($this->once())->method('getStore')->willReturn($storeMock);
        $this->wrappingMock->expects($this->once())->method('setStoreId')->willReturnSelf();
        $this->wrappingMock->expects($this->once())->method('load')->willReturnSelf();
        $priceCurrencyMock = $this->getMockForAbstractClass(PriceCurrencyInterface::class);
        $helperMock->expects($this->any())->method('getPrintedCardPrice');
        $priceCurrencyMock->expects($this->any())->method('convert')->willReturn(10);
        $shippingMock = $this->getMockForAbstractClass(ShippingInterface::class);
        $shippingMock->expects($this->atLeastOnce())->method('getAddress')->willReturn($addressMock);
        $shippingAssignmentMock = $this->getMockForAbstractClass(ShippingAssignmentInterface::class);
        $shippingAssignmentMock->expects($this->atLeastOnce())->method('getShipping')->willReturn($shippingMock);
        $shippingAssignmentMock->expects($this->once())->method('getItems')->willReturn([$item]);
        $totalMock = $this->createMock(Total::class);
        $model = new Giftwrapping(
            $helperMock,
            $priceCurrencyMock,
            $wrappingFactoryMock
        );
        $model->collect($quote, $shippingAssignmentMock, $totalMock);
    }
}
