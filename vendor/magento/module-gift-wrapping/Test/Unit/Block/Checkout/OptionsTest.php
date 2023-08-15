<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Block\Checkout;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Block\Checkout\Options;
use Magento\GiftWrapping\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OptionsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Options
     */
    protected $block;

    /**
     * @var MockObject
     */
    protected $wrappingDataMock;

    /**
     * @var MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var \Magento\Checkout\Model\CartFactory|MockObject
     */
    protected $checkoutCartFactoryMock;

    /**
     * @var MockObject
     */
    protected $pricingHelperMock;

    /**
     * @var MockObject
     */
    private $taxClassfactoryMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $checkoutItems = [
            'onepage' => [
                'order_level' => 'quote',
                'item_level' => 'quote_item',
            ],
            'multishipping' => [
                'order_level' => 'quote_address',
                'item_level' => 'quote_address_item',
            ],
        ];

        $this->checkoutSessionMock = $this->createPartialMock(Session::class, ['getQuote']);
        $this->wrappingDataMock = $this->createMock(Data::class);
        $this->pricingHelperMock = $this->createMock(\Magento\Framework\Pricing\Helper\Data::class);
        $this->checkoutCartFactoryMock = $this->getMockBuilder(\Magento\Checkout\Model\CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->taxClassfactoryMock = $this->createPartialMock(
            \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory::class,
            ['create']
        );

        $this->block = $this->objectManager->getObject(
            Options::class,
            [
                'checkoutSession' => $this->checkoutSessionMock,
                'checkoutItems' => $checkoutItems,
                'giftWrappingData' => $this->wrappingDataMock,
                'checkoutCartFactory' => $this->checkoutCartFactoryMock,
                'pricingHelper' => $this->pricingHelperMock
            ]
        );
        $this->objectManager->setBackwardCompatibleProperty(
            $this->block,
            'taxClassKeyFactory',
            $this->taxClassfactoryMock
        );
    }

    /**
     * @dataProvider getCheckoutTypeVariableDataProvider
     * @param bool $isMultiShipping
     * @param string $level
     * @param string $expectedResult
     */
    public function testGetCheckoutTypeVariable($isMultiShipping, $level, $expectedResult)
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getIsMultiShipping'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->expects($this->once())
            ->method('getIsMultiShipping')
            ->willReturn($isMultiShipping);

        $this->checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->assertEquals($expectedResult, $this->block->getCheckoutTypeVariable($level));
    }

    public function getCheckoutTypeVariableDataProvider()
    {
        return [
            'onepage_order_level' => [false, 'order_level', 'quote'],
            'onepage_item_level' => [false, 'item_level', 'quote_item'],
            'multishipping_order_level' => [true, 'order_level', 'quote_address'],
            'multishipping_item_level' => [true, 'item_level', 'quote_address_item'],
        ];
    }

    public function testGetCheckoutTypeVariableException()
    {
        $level = 'wrong_level';
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getIsMultiShipping'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->expects($this->once())
            ->method('getIsMultiShipping')
            ->willReturn(false);

        $this->checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid level: ' . $level);
        $this->block->getCheckoutTypeVariable($level);
    }

    public function testGetDisplayWrappingBothPrices()
    {
        $this->wrappingDataMock->expects($this->once())
            ->method('displayCartWrappingBothPrices')
            ->willReturn(true);
        $this->assertTrue($this->block->getDisplayWrappingBothPrices());
    }

    public function testGetDisplayCardBothPrices()
    {
        $this->wrappingDataMock->expects($this->once())
            ->method('displayCartCardBothPrices')
            ->willReturn(true);
        $this->assertTrue($this->block->getDisplayCardBothPrices());
    }

    public function testGetDisplayWrappingIncludeTaxPrice()
    {
        $this->wrappingDataMock->expects($this->once())
            ->method('displayCartWrappingIncludeTaxPrice')
            ->willReturn(true);
        $this->assertTrue($this->block->getDisplayWrappingIncludeTaxPrice());
    }

    public function testGetDisplayCardIncludeTaxPrice()
    {
        $this->wrappingDataMock->expects($this->once())
            ->method('displayCartCardIncludeTaxPrice')
            ->willReturn(true);
        $this->assertTrue($this->block->getDisplayCardIncludeTaxPrice());
    }

    public function testGetAllowPrintedCard()
    {
        $this->wrappingDataMock->expects($this->once())
            ->method('allowPrintedCard')
            ->willReturn(true);
        $this->assertTrue($this->block->getAllowPrintedCard());
    }

    public function testGetAllowGiftReceipt()
    {
        $this->wrappingDataMock->expects($this->once())
            ->method('allowGiftReceipt')
            ->willReturn(true);
        $this->assertTrue($this->block->getAllowGiftReceipt());
    }

    public function testGetAllowForOrder()
    {
        $this->wrappingDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')
            ->willReturn(true);
        $this->assertTrue($this->block->getAllowForOrder());
    }

    public function testGetAllowForItems()
    {
        $this->wrappingDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn(true);
        $this->assertTrue($this->block->getAllowForItems());
    }

    public function testCalculatePrice()
    {
        $includeTax = true;
        $basePrice = 99.99;
        $price = 109.99;
        $taxClass = 'tax_class';
        $currency = 100.00;
        $itemMock =
            $this->getMockBuilder(DataObject::class)
                ->addMethods(['setTaxClassId', 'setTaxClassKey'])
                ->disableOriginalConstructor()
                ->getMock();
        $shipAddressMock = $this->createMock(Address::class);
        $taxClassKeyMock = $this->getMockForAbstractClass(TaxClassKeyInterface::class);
        $quoteMock =
            $this->createPartialMock(Quote::class, ['getBillingAddress']);
        $this->checkoutSessionMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);

        $billAddressMock = $this->createMock(Address::class);
        $quoteMock->expects($this->once())->method('getBillingAddress')->willReturn($billAddressMock);

        $this->wrappingDataMock->expects($this->once())
            ->method('getWrappingTaxClass')
            ->willReturn($taxClass);
        $itemMock->expects($this->once())->method('setTaxClassId')->with($taxClass)->willReturnSelf();

        $this->wrappingDataMock->expects($this->once())
            ->method('getPrice')
            ->with($itemMock, $basePrice, $includeTax, $shipAddressMock, $billAddressMock)
            ->willReturn($price);

        $this->pricingHelperMock->expects($this->once())
            ->method('currency')
            ->with($price, true, false)
            ->willReturn($currency);

        $this->taxClassfactoryMock->expects($this->once())->method('create')->willReturn($taxClassKeyMock);
        $taxClassKeyMock
            ->expects($this->once())
            ->method('setType')
            ->with(TaxClassKeyInterface::TYPE_ID);
        $itemMock->expects($this->once())->method('setTaxClassKey')->with($taxClassKeyMock);
        $taxClassKeyMock->expects($this->once())->method('setValue')->with($taxClass);
        $this->assertEquals(
            $currency,
            $this->block->calculatePrice($itemMock, $basePrice, $shipAddressMock, $includeTax)
        );
    }

    /**
     * @param bool $giftWrappingAvailable
     * @param bool $allowForOrder
     * @param bool $allowForItems
     * @param bool $allowPrintedCard
     * @param bool $allowGiftReceipt
     * @param bool $expectedResult
     * @dataProvider dataProviderCanDisplayGiftWrapping
     */
    public function testCanDisplayGiftWrapping(
        $giftWrappingAvailable,
        $allowForOrder,
        $allowForItems,
        $allowPrintedCard,
        $allowGiftReceipt,
        $expectedResult
    ) {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGiftWrappingAvailable'])
            ->getMock();
        $productMock->expects($this->once())
            ->method('getGiftWrappingAvailable')
            ->willReturn($giftWrappingAvailable);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);
        $checkoutCartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutCartMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$itemMock]);

        $this->checkoutCartFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($checkoutCartMock);

        $this->wrappingDataMock->expects($this->any())
            ->method('isGiftWrappingAvailableForOrder')
            ->willReturn($allowForOrder);
        $this->wrappingDataMock->expects($this->any())
            ->method('isGiftWrappingAvailableForItems')
            ->willReturn($allowForItems);
        $this->wrappingDataMock->expects($this->any())
            ->method('allowPrintedCard')
            ->willReturn($allowPrintedCard);
        $this->wrappingDataMock->expects($this->any())
            ->method('allowGiftReceipt')
            ->willReturn($allowGiftReceipt);
        $this->assertEquals($expectedResult, $this->block->canDisplayGiftWrapping());
    }

    public function dataProviderCanDisplayGiftWrapping()
    {
        return [
            'item_true' => [
                'gift_wrapping_available' => true,
                'allow_for_order' => false,
                'allow_for_items' => false,
                'allow_printed_card' => false,
                'allow_gift_receipt' => false,
                'expected_result' => true,
            ],
            'allow_for_order' => [
                'gift_wrapping_available' => false,
                'allow_for_order' => true,
                'allow_for_items' => true,
                'allow_printed_card' => false,
                'allow_gift_receipt' => false,
                'expected_result' => true,
            ],
            'allow_for_items' => [
                'gift_wrapping_available' => false,
                'allow_for_order' => false,
                'allow_for_items' => true,
                'allow_printed_card' => false,
                'allow_gift_receipt' => false,
                'expected_result' => true,
            ],
            'allow_printed_card' => [
                'gift_wrapping_available' => false,
                'allow_for_order' => false,
                'allow_for_items' => false,
                'allow_printed_card' => true,
                'allow_gift_receipt' => false,
                'expected_result' => true,
            ],
            'allow_gift_receipt' => [
                'gift_wrapping_available' => false,
                'allow_for_order' => false,
                'allow_for_items' => false,
                'allow_printed_card' => false,
                'allow_gift_receipt' => true,
                'expected_result' => true,
            ],
            'false' => [
                'gift_wrapping_available' => false,
                'allow_for_order' => false,
                'allow_for_items' => false,
                'allow_printed_card' => false,
                'allow_gift_receipt' => false,
                'expected_result' => false,
            ],
        ];
    }
}
