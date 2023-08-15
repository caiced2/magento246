<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Model;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\AdvancedCheckout\Model\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\CartConfiguration;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\CatalogInventory\Helper\Stock;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\CatalogInventory\Model\StockState;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Message\Factory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Session\SessionManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\WishlistFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CartTest extends TestCase
{
    /**
     * @var Cart
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $localeFormatMock;

    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    /**
     * @var MockObject
     */
    protected $helperMock;

    /**
     * @var MockObject
     */
    protected $itemServiceMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    protected $productRepository;

    /**
     * @var MockObject
     */
    protected $stockRegistry;

    /** @var MockObject */
    protected $stockItemMock;

    /** @var MockObject */
    protected $stockState;

    /** @var MockObject */
    protected $stockHelper;

    /** @var MockObject */
    protected $quoteMock;

    /** @var MockObject */
    protected $quoteRepositoryMock;

    /** @var MockObject */
    protected $quoteFactoryMock;

    /** @var MockObject */
    protected $serializer;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $cartMock = $this->createMock(\Magento\Checkout\Model\Cart::class);
        $messageFactoryMock = $this->createMock(Factory::class);
        $eventManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->serializer = $this->createMock(Json::class);
        $wishListFactoryMock = $this->createPartialMock(WishlistFactory::class, ['create']);
        $this->quoteMock = $this->createPartialMock(Quote::class, ['getStore']);
        $this->quoteRepositoryMock = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->localeFormatMock = $this->getMockForAbstractClass(FormatInterface::class);
        $messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $customerSessionMock = $this->createMock(Session::class);

        $this->productRepository = $this->getMockForAbstractClass(ProductRepositoryInterface::class);
        $optionFactoryMock = $this->createPartialMock(OptionFactory::class, ['create']);
        $prodTypesConfigMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $cartConfigMock = $this->createMock(CartConfiguration::class);

        $this->stockRegistry = $this->getMockBuilder(StockRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStockItem'])
            ->getMock();

        $this->stockItemMock = $this->createPartialMock(
            Item::class,
            ['getQtyIncrements', 'getIsInStock', 'getMaxSaleQty', 'getMinSaleQty']
        );

        $this->stockRegistry->expects($this->any())
            ->method('getStockItem')
            ->willReturn($this->stockItemMock);

        $this->stockState = $this->createMock(StockState::class);

        $this->stockHelper = $this->createMock(Stock::class);
        $this->quoteFactoryMock = $this->createPartialMock(QuoteFactory::class, ['create']);
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new Cart(
            $cartMock,
            $messageFactoryMock,
            $eventManagerMock,
            $this->helperMock,
            $optionFactoryMock,
            $wishListFactoryMock,
            $this->quoteRepositoryMock,
            $this->storeManagerMock,
            $this->localeFormatMock,
            $messageManagerMock,
            $prodTypesConfigMock,
            $cartConfigMock,
            $customerSessionMock,
            $this->stockRegistry,
            $this->stockState,
            $this->stockHelper,
            $this->productRepository,
            $this->quoteFactoryMock,
            Data::ADD_ITEM_STATUS_FAILED_SKU,
            [],
            $this->serializer,
            $this->searchCriteriaBuilder
        );
    }

    /**
     * @param string $sku
     * @param array $config
     * @param array $expectedResult
     *
     * @covers \Magento\AdvancedCheckout\Model\Cart::__construct
     * @covers \Magento\AdvancedCheckout\Model\Cart::setAffectedItemConfig
     * @covers \Magento\AdvancedCheckout\Model\Cart::getAffectedItemConfig
     * @dataProvider setAffectedItemConfigDataProvider
     */
    public function testSetAffectedItemConfig($sku, $config, $expectedResult)
    {
        $this->model->setAffectedItemConfig($sku, $config);
        $this->assertEquals($expectedResult, $this->model->getAffectedItemConfig($sku));
    }

    /**
     * @return array
     */
    public function setAffectedItemConfigDataProvider()
    {
        return [
            [
                'sku' => 123,
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => 0,
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => 'aaa',
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => '',
                'config' => ['1'],
                'expectedResult' => []
            ],
            [
                'sku' => false,
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => null,
                'config' => ['1'],
                'expectedResult' => [1]
            ],
            [
                'sku' => 'aaa',
                'config' => [],
                'expectedResult' => []
            ],
            [
                'sku' => 'aaa',
                'config' => null,
                'expectedResult' => []
            ],
            [
                'sku' => 'aaa',
                'config' => false,
                'expectedResult' => []
            ],
            [
                'sku' => 'aaa',
                'config' => 0,
                'expectedResult' => []
            ],
            [
                'sku' => 'aaa',
                'config' => '',
                'expectedResult' => []
            ]
        ];
    }

    /**
     * @param string $sku
     * @param integer $qty
     * @param string $expectedCode
     *
     * @dataProvider prepareAddProductsBySkuDataProvider
     * @covers \Magento\AdvancedCheckout\Model\Cart::_getValidatedItem
     * @covers \Magento\AdvancedCheckout\Model\Cart::_loadProductBySku
     * @covers \Magento\AdvancedCheckout\Model\Cart::checkItem
     */
    public function testGetValidatedItem($sku, $qty, $expectedCode)
    {
        $storeMock = $this->getMockBuilder(Store::class)
            ->addMethods(['getStore'])
            ->onlyMethods(['getId', 'getWebsiteId'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())->method('getStore')->willReturn(1);
        $storeMock->method('getId')->willReturn(1);
        $storeMock->expects($this->any())->method('getWebsiteId')->willReturn(1);

        $sessionMock = $this->getMockBuilder(SessionManager::class)
            ->addMethods(['getAffectedItems', 'setAffectedItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $sessionMock->expects($this->any())->method('getAffectedItems')->willReturn([]);

        $productMock = $this->createPartialMock(
            Product::class,
            ['getId', 'getWebsiteIds', 'isComposite', 'getSku', '__sleep']
        );
        $productMock->expects($this->any())->method('getId')->willReturn(1);
        $productMock->expects($this->any())->method('getWebsiteIds')->willReturn([1]);
        $productMock->method('getSku')->willReturn('testSKU');
        $productMock->expects($this->any())->method('isComposite')->willReturn(false);

        $this->productRepository->expects($this->any())->method('get')->with($sku)
            ->willReturn($productMock);
        $this->helperMock->expects($this->any())->method('getSession')->willReturn($sessionMock);
        $this->localeFormatMock->expects($this->any())->method('getNumber')->willReturnArgument(0);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);
        $item = $this->model->checkItem($sku, $qty);

        $this->assertEquals($expectedCode, $item['code']);
    }

    /**
     * Test checkItem for item with config.
     */
    public function testGetValidatedItemWithConfig()
    {
        $config = ['options' => [1 => 2]];
        $jsonConfig = json_encode($config);
        $this->serializer->expects($this->once())->method('serialize')->willReturn($jsonConfig);
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getWebsiteId'])
            ->getMock();
        $storeMock->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn(1);
        $storeMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getWebsiteIds', 'isComposite'])
            ->getMock();
        $productMock->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $productMock->expects($this->atLeastOnce())->method('getWebsiteIds')->willReturn([1]);
        $productMock->expects($this->atLeastOnce())->method('isComposite')->willReturn(false);

        $this->productRepository->expects($this->atLeastOnce())->method('get')->with('test', false, 1)
            ->willReturn($productMock);
        $this->localeFormatMock->expects($this->atLeastOnce())->method('getNumber')->willReturnArgument(0);
        $this->storeManagerMock->expects($this->atLeastOnce())->method('getStore')->willReturn($storeMock);
        $item = $this->model->checkItem('test', 2, $config);

        $this->assertEquals(Data::ADD_ITEM_STATUS_SUCCESS, $item['code']);
    }

    /**
     * @return array
     */
    public function prepareAddProductsBySkuDataProvider()
    {
        return [
            [
                'sku' => 'aaa',
                'qty' => 2,
                'expectedCode' => Data::ADD_ITEM_STATUS_SUCCESS,
            ],
            [
                'sku' => 'aaa',
                'qty' => 'aaa',
                'expectedCode' => Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NUMBER,
            ],
            [
                'sku' => 'aaa',
                'qty' => -1,
                'expectedCode' => Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NON_POSITIVE,
            ],
            [
                'sku' => 'aaa',
                'qty' => 0.00001,
                'expectedCode' => Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_RANGE,
            ],
            [
                'sku' => 'aaa',
                'qty' => 100000000.0,
                'expectedCode' => Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_RANGE,
            ],
            [
                'sku' => 'a',
                'qty' => 2,
                'expectedCode' => Data::ADD_ITEM_STATUS_SUCCESS,
            ],
            [
                'sku' => 123,
                'qty' => 2,
                'expectedCode' => Data::ADD_ITEM_STATUS_SUCCESS,
            ],
            [
                'sku' => 0,
                'qty' => 2,
                'expectedCode' => Data::ADD_ITEM_STATUS_SUCCESS,
            ],
            [
                'sku' => '',
                'qty' => 2,
                'expectedCode' => Data::ADD_ITEM_STATUS_FAILED_EMPTY,
            ]
        ];
    }

    /**
     * @param array $config
     * @param array $result
     * @dataProvider getQtyStatusDataProvider
     * @TODO refactor me
     */
    public function testGetQtyStatus($config, $result)
    {
        $websiteId = 10;
        $productId = $config['product_id'];
        $requestQty = $config['request_qty'];

        $store = $this->createMock(Store::class);
        $store->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->quoteMock->expects($this->any())
            ->method('getStore')
            ->willReturn($store);

        $this->quoteMock->expects($this->any())
            ->method('getStore')
            ->willReturn($store);

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->quoteMock);

        $product = $this->createMock(Product::class);
        $product->expects($this->any())
            ->method('getId')
            ->willReturn($productId);

        $resultObject = new DataObject($config['result']);
        $this->stockState->expects($this->once())
            ->method('checkQuoteItemQty')
            ->with(
                $productId,
                $requestQty,
                $requestQty,
                $requestQty,
                $websiteId
            )
            ->willReturn($resultObject);

        if ($config['result']['has_error']) {
            switch ($resultObject->getErrorCode()) {
                case 'qty_increments':
                    $this->stockItemMock->expects($this->once())
                        ->method('getQtyIncrements')
                        ->willReturn($config['result']['qty_increments']);
                    break;
                case 'qty_min':
                    $this->stockItemMock->expects($this->once())
                        ->method('getMinSaleQty')
                        ->willReturn($config['result']['qty_min_allowed']);
                    break;
                case 'qty_max':
                    $this->stockItemMock->expects($this->once())
                        ->method('getMaxSaleQty')
                        ->willReturn($config['result']['qty_max_allowed']);
                    break;
                default:
                    $this->stockState->expects($this->once())
                        ->method('getStockQty')
                        ->with($productId)
                        ->willReturn($config['result']['qty_max_allowed']);
                    break;
            }
        }
        $this->assertSame($result, $this->model->getQtyStatus($product, $requestQty));
    }

    /**
     * @return array
     */
    public function getQtyStatusDataProvider()
    {
        return [
            'error qty_increments' => [
                [
                    'product_id' => 11,
                    'request_qty' => 6,
                    'result' => [
                        'has_error' => true,
                        'error_code' => 'qty_increments',
                        'qty_increments' => 1,
                        'message' => 'hello qty_increments'
                    ]
                ],
                [
                    'qty_increments' => 1,
                    'status' => Data::ADD_ITEM_STATUS_FAILED_QTY_INCREMENTS,
                    'error' => 'hello qty_increments'
                ]
            ],
            'error qty_min' => [
                [
                    'product_id' => 14,
                    'request_qty' => 5,
                    'result' => [
                        'has_error' => true,
                        'error_code' => 'qty_min',
                        'qty_min_allowed' => 2,
                        'message' => 'hello qty_min_allowed'
                    ]
                ],
                [
                    'qty_min_allowed' => 2,
                    'status' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART,
                    'error' => 'hello qty_min_allowed'
                ]
            ],
            'error qty_max' => [
                [
                    'product_id' => 13,
                    'request_qty' => 4,
                    'result' => [
                        'has_error' => true,
                        'error_code' => 'qty_max',
                        'qty_max_allowed' => 3,
                        'message' => 'hello qty_max_allowed'
                    ]
                ],
                [
                    'qty_max_allowed' => 3,
                    'status' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART,
                    'error' => 'hello qty_max_allowed'
                ]
            ],
            'error default' => [
                [
                    'product_id' => 12,
                    'request_qty' => 3,
                    'result' => [
                        'has_error' => true,
                        'error_code' => 'default',
                        'qty_max_allowed' => 4,
                        'message' => 'hello default'
                    ]
                ],
                [
                    'qty_max_allowed' => 4,
                    'status' => Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED,
                    'error' => 'hello default'
                ]
            ],
            'no error' => [
                [
                    'product_id' => 18,
                    'request_qty' => 22,
                    'result' => ['has_error' => false]
                ],
                true
            ],
        ];
    }

    public function testAdditionalOptionsInReorderItem()
    {
        $additionalOptionResult = ['additional_option' => 1];

        $storeMock = $this->createMock(Store::class);
        $productMock = $this->createMock(Product::class);
        $quoteItemMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $quoteMock = $this->createPartialMock(Quote::class, ['getStore', 'addProduct']);
        $orderItemMock = $this->createMock(\Magento\Sales\Model\Order\Item::class);

        $orderItemMock->expects($this->any())->method('getProductOptionByCode')->willReturnMap([
            ['info_buyRequest', []],
            ['additional_options', $additionalOptionResult]
        ]);

        $storeMock->expects($this->any())->method('getId')->willReturn(1);
        $orderItemMock->expects($this->once())->method('getId')->willReturn(1);
        $this->quoteFactoryMock->expects($this->once())->method('create')->willReturn($quoteMock);
        $quoteMock->expects($this->any())->method('getStore')->willReturn($storeMock);
        $quoteMock->expects($this->any())->method('addProduct')->willReturn($quoteItemMock);
        $this->productRepository->expects($this->any())->method('getById')->willReturn($productMock);
        $this->serializer->expects($this->once())->method('serialize')->with($additionalOptionResult);

        $this->model->reorderItem($orderItemMock, 1);
    }
}
