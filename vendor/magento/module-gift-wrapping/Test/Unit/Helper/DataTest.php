<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Helper;

use Magento\Catalog\Model\Product\Attribute\Source\Boolean;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Helper\Data;
use Magento\GiftWrapping\Model\System\Config\Source\Display\Type;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Tax\Api\Data\QuoteDetailsInterface;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxDetailsInterface;
use Magento\Tax\Api\Data\TaxDetailsItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var MockObject
     */
    protected $storeManager;

    /**
     * @var MockObject
     */
    protected $quoteDetailsItemFactory;

    /**
     * @var MockObject
     */
    protected $quoteDetailsFactory;

    /**
     * @var MockObject
     */
    protected $taxCalculationService;

    /**
     * @var MockObject
     */
    protected $priceCurrency;

    /**
     * @var Data
     */
    protected $subject;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $className = Data::class;
        $arguments = $objectManager->getConstructArguments($className);
        /** @var Context $context */
        $context = $arguments['context'];
        $this->scopeConfigMock = $context->getScopeConfig();
        $this->storeManager = $arguments['storeManager'];
        $this->quoteDetailsItemFactory =
            $this->getMockBuilder(\Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMockForAbstractClass();
        $this->quoteDetailsFactory = $this->getMockBuilder(\Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->taxCalculationService = $arguments['taxCalculationService'];
        $this->priceCurrency = $arguments['priceCurrency'];

        $arguments['quoteDetailsItemFactory'] = $this->quoteDetailsItemFactory;
        $arguments['quoteDetailsFactory'] = $this->quoteDetailsFactory;

        $this->subject = $objectManager->getObject($className, $arguments);
    }

    /**
     * @param bool $useBillingAddress
     * @dataProvider getPriceDataProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetPrice($useBillingAddress)
    {
        $customerId = 5494;
        $storeId = 2;
        $taxClassKeyValue = 13;
        $item = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getTaxClassKey'])
            ->disableOriginalConstructor()
            ->getMock();
        $price = 12.45;
        $includeTax = true;
        $shippingAddress = $this->createMock(Address::class);
        $billingAddress = $this->createMock(Address::class);
        $shippingDataModel = $this->getMockForAbstractClass(
            AddressInterface::class,
            [],
            'shippingDataModel',
            false
        );

        $billingDataModel = null;
        if ($useBillingAddress) {
            $billingDataModel = $this->getMockForAbstractClass(
                AddressInterface::class,
                [],
                'billingDataMode',
                false
            );
            $billingDataModel->expects($this->once())
                ->method('getCustomerId')
                ->willReturn($customerId);
        } else {
            $shippingDataModel->expects($this->once())
                ->method('getCustomerId')
                ->willReturn($customerId);
        }

        $shippingAddress->expects($this->once())
            ->method('getDataModel')
            ->willReturn($shippingDataModel);
        $billingAddress->expects($this->once())
            ->method('getDataModel')
            ->willReturn($billingDataModel);

        $store = $this->createMock(Store::class);
        $store->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $taxClassKey = $this->getMockForAbstractClass(TaxClassKeyInterface::class, [], '', false);
        $taxClassKey->expects($this->once())
            ->method('getValue')
            ->willReturn($taxClassKeyValue);
        $item->expects($this->once())
            ->method('getTaxClassKey')
            ->willReturn($taxClassKey);

        $quoteDetailsItem = $this->getMockForAbstractClass(
            QuoteDetailsItemInterface::class,
            [],
            '',
            false
        );
        $quoteDetailsItem->expects($this->once())
            ->method('setQuantity')
            ->with(1)
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setCode')
            ->with('giftwrapping_code')
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setTaxClassId')
            ->with($taxClassKeyValue)
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setIsTaxIncluded')
            ->with(false)
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setType')
            ->with('giftwrapping_type')
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setTaxClassKey')
            ->with($taxClassKey)
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setUnitPrice')
            ->with($price)
            ->willReturnSelf();

        $this->quoteDetailsItemFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($quoteDetailsItem);
        $quoteDetails = $this->getMockForAbstractClass(
            QuoteDetailsInterface::class,
            [],
            '',
            false
        );
        $quoteDetails->expects($this->once())
            ->method('setShippingAddress')
            ->with($shippingDataModel)
            ->willReturnSelf();
        $quoteDetails->expects($this->once())
            ->method('setBillingAddress')
            ->with($billingDataModel)
            ->willReturnSelf();
        $quoteDetails->expects($this->once())
            ->method('setCustomerTaxClassId')
            ->with(null)
            ->willReturnSelf();
        $quoteDetails->expects($this->once())
            ->method('setItems')
            ->with([$quoteDetailsItem])
            ->willReturnSelf();
        $quoteDetails->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->quoteDetailsFactory->expects($this->once())
            ->method('create')
            ->willReturn($quoteDetails);

        $taxDetailItem = $this->getMockForAbstractClass(
            TaxDetailsItemInterface::class,
            [],
            '',
            false
        );
        $taxDetailItem->expects($this->once())
            ->method('getPriceInclTax')
            ->willReturn($price);
        $taxDetail = $this->getMockForAbstractClass(TaxDetailsInterface::class, [], '', false);
        $taxDetail->expects($this->once())
            ->method('getItems')
            ->willReturn([$taxDetailItem]);
        $this->taxCalculationService->expects($this->once())
            ->method('calculateTax')
            ->with($quoteDetails, $storeId, true)
            ->willReturn($taxDetail);

        $this->subject->getPrice($item, $price, $includeTax, $shippingAddress, $billingAddress);
    }

    /**
     * @return array
     */
    public function getPriceDataProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    public function testGetPriceWithoutTaxCalculation()
    {
        $item = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getTaxClassKey'])
            ->disableOriginalConstructor()
            ->getMock();
        $price = 12;
        $includeTax = false;
        $shippingAddress = $this->createMock(Address::class);
        $billingAddress = $this->createMock(Address::class);

        $store = $this->createMock(Store::class);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $taxClassKey = $this->getMockForAbstractClass(TaxClassKeyInterface::class, [], '', false);
        $item->expects($this->once())
            ->method('getTaxClassKey')
            ->willReturn($taxClassKey);

        $this->priceCurrency
            ->expects($this->once())
            ->method('round')
            ->with($price)
            ->willReturn($price);

        $this->subject->getPrice($item, $price, $includeTax, $shippingAddress, $billingAddress);
    }

    public function testIsGiftWrappingAvailableIfProductConfigIsNull()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_ALLOWED_FOR_ITEMS,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn($scopeConfig);
        $this->assertEquals($scopeConfig, $this->subject->isGiftWrappingAvailableForProduct(null, $storeMock));
    }

    /**
     * @param int $expectedResult
     * @param int $configValue
     * @param mixed $productValue
     * @dataProvider productConfigDataProvider
     */
    public function testIsGiftWrappingAvailableForProduct($expectedResult, $configValue, $productValue)
    {
        $storeMock = $this->createMock(Store::class);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(
                Data::XML_PATH_ALLOWED_FOR_ITEMS,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn($configValue);

        $this->assertEquals(
            $expectedResult,
            $this->subject->isGiftWrappingAvailableForProduct($productValue, $storeMock)
        );
    }

    /**
     * @return array
     */
    public function productConfigDataProvider()
    {
        return [
            [1 , 1, ''],
            [1 , 1, null],
            [1 , 1, Boolean::VALUE_USE_CONFIG],
            [0 , 1, 0],
            [1, 0, 1],
        ];
    }

    public function testIsGiftWrappingAvailableForItems()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_ALLOWED_FOR_ITEMS,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn($scopeConfig);
        $this->assertEquals($scopeConfig, $this->subject->isGiftWrappingAvailableForItems($storeMock));
    }

    public function testIsGiftWrappingAvailableForOrder()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_ALLOWED_FOR_ORDER,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn($scopeConfig);
        $this->assertEquals($scopeConfig, $this->subject->isGiftWrappingAvailableForOrder($storeMock));
    }

    public function testGetWrappingTaxClass()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_TAX_CLASS,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn($scopeConfig);
        $this->assertEquals($scopeConfig, $this->subject->getWrappingTaxClass($storeMock));
    }

    public function testAllowPrintedCard()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_ALLOW_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn($scopeConfig);
        $this->assertEquals($scopeConfig, $this->subject->allowPrintedCard($storeMock));
    }

    public function testAllowGiftReceipt()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_ALLOW_GIFT_RECEIPT,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn($scopeConfig);
        $this->assertEquals($scopeConfig, $this->subject->allowGiftReceipt($storeMock));
    }

    public function testGetPrintedCardPrice()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRINTED_CARD_PRICE,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn($scopeConfig);
        $this->assertEquals($scopeConfig, $this->subject->getPrintedCardPrice($storeMock));
    }

    public function testDisplayCartWrappingIncludeTaxPriceWhenDisplayTypeIsBoth()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_BOTH);
        $this->assertTrue($this->subject->displayCartWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingIncludeTaxPriceWhenDisplayTypeIsIncludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_INCLUDING_TAX);
        $this->assertTrue($this->subject->displayCartWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingIncludeTaxPriceWhenDisplayTypeIsExcludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_EXCLUDING_TAX);
        $this->assertFalse($this->subject->displayCartWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingExcludeTaxPriceWhenDisplayTypeIsIncludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_INCLUDING_TAX);
        $this->assertFalse($this->subject->displayCartWrappingExcludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingExcludeTaxPriceWhenDisplayTypeIsExcludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_EXCLUDING_TAX);
        $this->assertTrue($this->subject->displayCartWrappingExcludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingBothPricesIsIncludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_EXCLUDING_TAX);
        $this->assertFalse($this->subject->displayCartWrappingBothPrices($storeMock));
    }

    public function testDisplayCartWrappingBothPricesIsBoth()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_BOTH);
        $this->assertTrue($this->subject->displayCartWrappingBothPrices($storeMock));
    }

    public function testDisplayCartCardIncludeTaxPriceIsBoth()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_BOTH);
        $this->assertTrue($this->subject->displayCartCardIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartCardIncludeTaxPriceIsExcludingTaxPrice()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_EXCLUDING_TAX);
        $this->assertFalse($this->subject->displayCartCardIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartCardIncludeTaxPriceIsIncludingTaxPrice()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_INCLUDING_TAX);
        $this->assertTrue($this->subject->displayCartCardIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartCardBothPricesIncludingTaxPrice()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_INCLUDING_TAX);
        $this->assertFalse($this->subject->displayCartCardBothPrices($storeMock));
    }

    public function testDisplayCartCardBothPricesDisplayTypeBoth()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_BOTH);
        $this->assertTrue($this->subject->displayCartCardBothPrices($storeMock));
    }

    public function testDisplaySalesWrappingIncludeTaxPriceDisplayTypeBoth()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_BOTH);
        $this->assertTrue($this->subject->displaySalesWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingIncludeTaxPriceDisplayTypeIncludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_INCLUDING_TAX);
        $this->assertTrue($this->subject->displaySalesWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingIncludeTaxPriceDisplayTypeExcludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_EXCLUDING_TAX);
        $this->assertFalse($this->subject->displaySalesWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingExcludeTaxPriceDisplayTypeExcludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_EXCLUDING_TAX);
        $this->assertTrue($this->subject->displaySalesWrappingExcludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingExcludeTaxPriceDisplayTypeIncludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_INCLUDING_TAX);
        $this->assertFalse($this->subject->displaySalesWrappingExcludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingBothPricesDisplayTypeIncludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_INCLUDING_TAX);
        $this->assertFalse($this->subject->displaySalesWrappingBothPrices($storeMock));
    }

    public function testDisplaySalesWrappingBothPricesDisplayTypeBoth()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_BOTH);
        $this->assertTrue($this->subject->displaySalesWrappingBothPrices($storeMock));
    }

    public function testDisplaySalesCardIncludeTaxPriceDisplayTypeBoth()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_BOTH);
        $this->assertTrue($this->subject->displaySalesCardIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesCardIncludeTaxPriceDisplayTypeIncludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_INCLUDING_TAX);
        $this->assertTrue($this->subject->displaySalesCardIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesCardIncludeTaxPriceDisplayTypeExcludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_EXCLUDING_TAX);
        $this->assertFalse($this->subject->displaySalesCardIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesCardBothPricesDisplayTypeExcludingTax()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_EXCLUDING_TAX);
        $this->assertFalse($this->subject->displaySalesCardBothPrices($storeMock));
    }

    public function testDisplaySalesCardBothPricesDisplayTypeBoth()
    {
        $storeMock = $this->createMock(Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->willReturn(Type::DISPLAY_TYPE_BOTH);
        $this->assertTrue($this->subject->displaySalesCardBothPrices($storeMock));
    }
}
