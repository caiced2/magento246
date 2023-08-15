<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Helper;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Url;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Checkout\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Msrp\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends TestCase
{
    /**
     * @var Cart|MockObject
     */
    protected $cart;

    /**
     * @var Collection|MockObject
     */
    protected $productCollection;

    /**
     * @var Config|MockObject
     */
    protected $catalogConfig;

    /**
     * @var SessionManagerInterface|MockObject
     */
    protected $sessionManager;

    /**
     * @var Session|MockObject
     */
    protected $checkoutSession;

    /**
     * @var StockRegistryInterface|MockObject
     */
    protected $stockRegistry;

    /**
     * @var Stock|MockObject
     */
    protected $stockHelper;

    /**
     * @var ItemFactory|MockObject
     */
    protected $quoteItemFactory;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    protected $priceCurrency;

    /**
     * @var Data|MockObject
     */
    protected $msrpData;

    /**
     * @var \Magento\AdvancedCheckout\Helper\Data
     */
    protected $dataHelper;

    protected function setUp(): void
    {
        $this->cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollection = $this->getMockBuilder(
            Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionManager = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockRegistry = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->stockHelper = $this->getMockBuilder(Stock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemFactory = $this->getMockBuilder(ItemFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->msrpData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->dataHelper = $objectManagerHelper->getObject(
            \Magento\AdvancedCheckout\Helper\Data::class,
            [
                'cart' => $this->cart,
                'products' => $this->productCollection,
                'catalogConfig' => $this->catalogConfig,
                'checkoutSession' => $this->checkoutSession,
                'stockRegistry' => $this->stockRegistry,
                'stockHelper' => $this->stockHelper,
                'quoteItemFactory' => $this->quoteItemFactory,
                'msrpData' => $this->msrpData
            ]
        );
    }

    /**
     * @dataProvider getFailedItemsDataProvider
     * @param $productOptions array|null
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetFailedItems($productOptions)
    {
        $code = 'failed_configure';
        $qty = 2;
        $item = [
            'sku' => 'product_sku',
            'qty' => $qty,
            'id' => '1',
            'is_qty_disabled' => false
        ];
        $failedItems = [
            'item' => $item,
            'code' => $code,
            'orig_qty' => $qty
        ];
        $productAttributesData = [
            'attribute_1',
            'attribute_2'
        ];
        $productData = [
            'entity_id' => '1',
            'sku' => 'product_sku'
        ];
        $productUrl = 'http://magetest.com/product1.html';
        $websiteId = '0';
        $customOption = ['custom_option'];

        $this->cart->expects($this->once())->method('getFailedItems')->willReturn([$failedItems]);
        $this->productCollection->expects($this->once())->method('addMinimalPrice')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('addFinalPrice')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('addTaxPercents')->willReturnSelf();
        $this->catalogConfig->expects($this->once())->method('getProductAttributes')
            ->willReturn([$productAttributesData]);
        $this->productCollection->expects($this->once())->method('addAttributeToSelect')->with([$productAttributesData])
            ->willReturnSelf();
        $this->productCollection->expects($this->once())->method('addUrlRewrite')->willReturnSelf();
        $quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSession->expects($this->once())->method('getQuote')->willReturn($quote);
        $this->productCollection->expects($this->once())->method('addIdFilter')->willReturnSelf();
        $this->productCollection->expects($this->once())->method('setFlag')->with('has_stock_status_filter', true)
            ->willReturnSelf();

        $quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(
                [
                    'setRedirectUrl',
                    'addData',
                    'setQuote',
                    'setProduct',
                    'getOptions',
                    'setOptions',
                    'setCanApplyMsrp',
                    'setStockItem'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemFactory->expects($this->once())->method('create')->willReturn($quoteItem);
        $productItem = $this->getMockBuilder(Product::class)
            ->setMethods(
                [
                    'getId',
                    'addData',
                    'getData',
                    'getUrlModel',
                    'getOptions',
                    'getOptionsByCode',
                    'setCustomOptions'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCollection->expects($this->once())->method('getItems')->willReturn([$productItem]);
        $productItem->expects($this->any())->method('getId')->willReturn($productData['entity_id']);
        $productItem->expects($this->once())->method('addData')->willReturnSelf();
        $productItem->expects($this->any())->method('getOptionsByCode')->willReturn($customOption);
        $productItem->expects($this->once())->method('getData')->willReturn($productData);
        $quoteItem->expects($this->once())->method('getOptions')->willReturn([]);
        $quoteItem->expects($this->once())->method('addData')->with($productData)->willReturnSelf();
        $quoteItem->expects($this->once())->method('setQuote')->with($quote)->willReturnSelf();
        $quoteItem->expects($this->once())->method('setProduct')->willReturnSelf();
        $productUrlModel = $this->getMockBuilder(Url::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productItem->expects($this->any())->method('getUrlModel')->willReturn($productUrlModel);
        $productUrlModel->expects($this->once())->method('getUrl')->with($productItem)
            ->willReturn($productUrl);
        $quoteItem->expects($this->once())->method('setRedirectUrl')->with($productUrl)->willReturnSelf();
        $productItem->expects($this->any())->method('getOptions')->willReturn($productOptions);
        $productItem->expects($this->once())->method('setCustomOptions')->with($customOption)->willReturnSelf();
        $this->msrpData->expects($this->once())->method('canApplyMsrp')->with($productItem)->willReturn(false);
        $quoteItem->expects($this->once())->method('setCanApplyMsrp')->with(false)->willReturnSelf();
        $quoteItem->expects($this->any())->method('setOptions')->with($productOptions)->willReturnSelf();
        $this->stockHelper->expects($this->once())->method('assignStatusToProduct')->willReturnSelf();
        $stockItem = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeModel = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->once())->method('getStore')->willReturn($storeModel);
        $storeModel->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->stockRegistry->expects($this->once())->method('getStockItem')
            ->with($productData['entity_id'], $websiteId)
            ->willReturn($stockItem);
        $quoteItem->expects($this->once())->method('setStockItem')->with($stockItem)->willReturnSelf();
        $this->dataHelper->getFailedItems(false);
    }

    /**
     * @return array
     */
    public function getFailedItemsDataProvider()
    {
        return [
            [
                null
            ],
            [
                ['product_options']
            ]
        ];
    }
}
