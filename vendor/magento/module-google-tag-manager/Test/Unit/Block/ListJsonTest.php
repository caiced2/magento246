<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Test\Unit\Block;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Checkout\Helper\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\GoogleTagManager\Block\ListJson;
use Magento\GoogleTagManager\Helper\Data;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ListJsonTest extends TestCase
{
    /** @var ListJson */
    protected $listJson;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Data|MockObject */
    protected $googleTagManagerHelper;

    /** @var \Magento\Framework\Json\Helper\Data|MockObject */
    protected $jsonHelper;

    /** @var Registry|MockObject */
    protected $registry;

    /** @var Session|MockObject */
    protected $checkoutSession;

    /** @var \Magento\Customer\Model\Session|MockObject */
    protected $customerSession;

    /** @var Cart|MockObject */
    protected $checkoutCartHelper;

    /** @var Layer|MockObject */
    protected $layer;

    /** @var Http|MockObject */
    protected $http;

    /** @var LayoutInterface|MockObject */
    protected $layout;

    /** @var StoreManagerInterface|MockObject */
    protected $storeManager;

    /** @var ScopeConfigInterface|MockObject */
    protected $scopeConfig;

    /**
     * @var TagManagerConfig|MockObject
     */
    private $tagManagerConfig;

    protected function setUp(): void
    {
        $this->googleTagManagerHelper = $this->createMock(Data::class);
        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->registry = $this->createMock(Registry::class);
        $this->checkoutSession = $this->createMock(Session::class);
        $this->customerSession = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->checkoutCartHelper = $this->createMock(Cart::class);
        $this->http = $this->createMock(Http::class);
        $this->layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->scopeConfig = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->tagManagerConfig = $this->getMockBuilder(TagManagerConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->createListJson(false);
    }

    protected function createListJson($initLayer = true)
    {
        $this->layer = $initLayer ?
            $this->createPartialMock(Layer::class, ['getCurrentCategory']) :
            null;
        $layerResolver = $this->createPartialMock(Resolver::class, ['get']);
        $layerResolver->expects($this->once())->method('get')->willReturn($this->layer);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->listJson = $this->objectManagerHelper->getObject(
            ListJson::class,
            [
                'helper' => $this->googleTagManagerHelper,
                'jsonHelper' => $this->jsonHelper,
                'registry' => $this->registry,
                'checkoutSession' => $this->checkoutSession,
                'customerSession' => $this->customerSession,
                'checkoutCart' => $this->checkoutCartHelper,
                'layerResolver' => $layerResolver,
                'request' => $this->http,
                'layout' => $this->layout,
                'storeManager' => $this->storeManager,
                'scopeConfig' => $this->scopeConfig,
                'tagManagerConfig' => $this->tagManagerConfig
            ]
        );
    }

    public function testToHtml()
    {
        $this->googleTagManagerHelper->expects($this->atLeastOnce())->method('isTagManagerAvailable')
            ->willReturn(true);
        $this->listJson->toHtml();
    }

    public function testGetListBlock()
    {
        $this->listJson->setBlockName('catalog.product.related');
        $block = $this->getMockForAbstractClass(BlockInterface::class);
        $this->layout->expects($this->atLeastOnce())->method('getBlock')->with('catalog.product.related')
            ->willReturn($block);

        $this->assertSame($block, $this->listJson->getListBlock());
    }

    public function testCheckCartItems()
    {
        $this->checkoutCartHelper->expects($this->atLeastOnce())->method('getItemsCount')->willReturn(0);
        $this->listJson->checkCartItems();
    }

    public function testGetLoadedProductCollectionForCatalogList()
    {
        $collection = $this->createMock(Collection::class);
        $category = $this->createPartialMock(Category::class, ['getDisplayMode']);
        $category->expects($this->atLeastOnce())->method('getDisplayMode')
            ->willReturn(Category::DM_PRODUCT);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('current_category')
            ->willReturn($category);

        $this->listJson->setBlockName('catalog.product.related');
        $block = $this->getMockBuilder(BlockInterface::class)
            ->addMethods(['getLoadedProductCollection'])
            ->onlyMethods(['toHtml'])
            ->getMockForAbstractClass();
        $block->expects($this->atLeastOnce())->method('getLoadedProductCollection')->willReturn($collection);

        $this->layout->expects($this->atLeastOnce())->method('getBlock')->with('catalog.product.related')
            ->willReturn($block);

        $this->assertSame($collection, $this->listJson->getLoadedProductCollection());
    }

    public function testGetLoadedProductCollectionForCrossSell()
    {
        $collection = $this->createMock(Collection::class);
        $category = $this->createMock(Category::class);
        $category->expects($this->atLeastOnce())->method('getDisplayMode')
            ->willReturn(Category::DM_PRODUCT);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('current_category')
            ->willReturn($category);

        $this->listJson->setBlockName('catalog.product.related');
        $block = $this->getMockBuilder(BlockInterface::class)
            ->addMethods(['getLoadedProductCollection', 'getItemCollection'])
            ->onlyMethods(['toHtml'])
            ->getMockForAbstractClass();
        $block->expects($this->atLeastOnce())->method('getItemCollection')->willReturn($collection);

        $this->layout->expects($this->atLeastOnce())->method('getBlock')->with('catalog.product.related')
            ->willReturn($block);

        $this->assertSame($collection, $this->listJson->getLoadedProductCollection());
    }

    public function testGetLoadedProductCollectionForRelated()
    {
        $collection = $this->createMock(Collection::class);
        $category = $this->createMock(Category::class);
        $category->expects($this->atLeastOnce())->method('getDisplayMode')
            ->willReturn(Category::DM_PRODUCT);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('current_category')
            ->willReturn($category);

        $this->listJson->setBlockName('catalog.product.related');
        $block = $this->getMockBuilder(BlockInterface::class)
            ->addMethods(['getLoadedProductCollection', 'getItemCollection', 'getItems'])
            ->onlyMethods(['toHtml'])
            ->getMockForAbstractClass();
        $block->expects($this->atLeastOnce())->method('getItems')->willReturn($collection);

        $this->layout->expects($this->atLeastOnce())->method('getBlock')->with('catalog.product.related')
            ->willReturn($block);

        $this->assertSame($collection, $this->listJson->getLoadedProductCollection());
        $this->assertSame($collection, $this->listJson->getLoadedProductCollection());
    }

    /**
     * @covers \Magento\GoogleTagManager\Block\ListJson::getCurrentCategory
     */
    public function testGetCurrentCategoryFromLayer()
    {
        $this->createListJson(true);
        $category = $this->createMock(Category::class);
        $this->layer->expects($this->atLeastOnce())->method('getCurrentCategory')->willReturn($category);
        $this->assertSame($category, $this->listJson->getCurrentCategory());
    }

    /**
     * @covers \Magento\GoogleTagManager\Block\ListJson::getCurrentCategory
     */
    public function testGetCurrentCategoryFromRegistry()
    {
        $category = $this->createMock(Category::class);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('current_category')
            ->willReturn($category);
        $this->assertSame($category, $this->listJson->getCurrentCategory());
    }

    public function testGetCurrentProduct()
    {
        $product = $this->createMock(Product::class);
        $this->registry->expects($this->atLeastOnce())->method('registry')->with('product')->willReturn($product);
        $this->assertSame($product, $this->listJson->getCurrentProduct());
    }

    /**
     * @param bool $showCategory
     * @param int $categoryId
     * @param string $expected
     *
     * @dataProvider getCurrentCategoryNameDataProvider
     */
    public function testGetCurrentCategoryName($showCategory, $categoryId, $expected)
    {
        $this->listJson->setShowCategory($showCategory);
        $category = $this->createMock(Category::class);
        $category->expects($this->any())->method('getId')->willReturn($categoryId);
        $category->expects($this->any())->method('getName')->willReturn('Category Name');
        $this->registry->expects($this->any())->method('registry')->with('current_category')
            ->willReturn($category);

        $store = $this->createMock(Store::class);
        $store->expects($this->any())->method('getRootCategoryId')->willReturn(2);
        $this->storeManager->expects($this->any())->method('getStore')->with(null)->willReturn($store);

        $this->assertEquals($expected, $this->listJson->getCurrentCategoryName());
    }

    public function getCurrentCategoryNameDataProvider()
    {
        return [
            [false, 0, ''],
            [true, 2, ''],
            [true, 5, 'Category Name']
        ];
    }

    /**
     * @param string $type
     * @param string $listPath
     * @param string $expected
     *
     * @dataProvider getCurrentListNameDataProvider
     */
    public function testGetCurrentListName($type, $listPath, $expected)
    {
        $this->listJson->setListType($type);
        $this->scopeConfig->expects($this->any())->method('getValue')->with($listPath)->willReturn($expected);
        $this->assertEquals($expected, $this->listJson->getCurrentListName());
    }

    public function getCurrentListNameDataProvider()
    {
        return [
            ['catalog', Data::XML_PATH_LIST_CATALOG_PAGE, 'catalog'],
            ['search', Data::XML_PATH_LIST_SEARCH_PAGE, 'search'],
            ['related', Data::XML_PATH_LIST_RELATED_BLOCK, 'related'],
            ['upsell', Data::XML_PATH_LIST_UPSELL_BLOCK, 'upsell'],
            ['crosssell', Data::XML_PATH_LIST_CROSSSELL_BLOCK, 'crosssell'],
            ['other', 'other', ''],
            ['', 'n/a', ''],
        ];
    }

    public function testGetBannerPosition()
    {
        $this->http->expects($this->atLeastOnce())->method('getFullActionName')->willReturn('actionName');
        $this->assertEquals('actionName', $this->listJson->getBannerPosition());
    }

    /**
     * @param bool $logged
     * @param string $expected
     *
     * @dataProvider detectStepNameDataProvider
     */
    public function testDetectStepName($logged, $expected)
    {
        $this->customerSession->expects($this->atLeastOnce())->method('isLoggedIn')->willReturn($logged);
        $this->listJson->detectStepName();
        $this->assertEquals($expected, $this->listJson->getStepName());
    }

    public function detectStepNameDataProvider()
    {
        return [
            [true, 'billing'],
            [false, 'login'],
        ];
    }

    public function testIsCustomerLoggedIn()
    {
        $this->customerSession->expects($this->atLeastOnce())->method('isLoggedIn')->willReturn(true);
        $this->assertTrue($this->listJson->isCustomerLoggedIn());
    }

    public function testGetCartContent()
    {
        $item = $this->createMock(Item::class);
        $item->expects($this->atLeastOnce())->method('getSku')->willReturn('SKU12323');
        $item->expects($this->atLeastOnce())->method('getName')->willReturn('Product Name');
        $item->expects($this->atLeastOnce())->method('getPrice')->willReturn(116);
        $item->expects($this->atLeastOnce())->method('getQty')->willReturn(2);

        $quote = $this->createMock(Quote::class);
        $quote->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$item]);
        $this->checkoutSession->expects($this->atLeastOnce())->method('getQuote')->willReturn($quote);
        $this->checkoutSession->expects($this->once())->method('start')->willReturnSelf();

        $json = [
            [
                'id' => 'SKU12323',
                'name' => 'Product Name',
                'price' => 116,
                'qty' => 2
            ]
        ];

        $this->jsonHelper->expects($this->once())->method('jsonEncode')->with($json)->willReturn('{encoded_string}');
        $this->assertEquals('{encoded_string}', $this->listJson->getCartContent());
    }

    public function testGetCartContentForUpdate()
    {
        $item = $this->createMock(Item::class);
        $item->expects($this->atLeastOnce())->method('getSku')->willReturn('SKU12323');
        $item->expects($this->atLeastOnce())->method('getName')->willReturn('Product Name');
        $item->expects($this->atLeastOnce())->method('getPrice')->willReturn(116);
        $item->expects($this->atLeastOnce())->method('getQty')->willReturn(2);

        $quote = $this->createMock(Quote::class);
        $quote->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$item]);
        $this->checkoutSession->expects($this->atLeastOnce())->method('getQuote')->willReturn($quote);
        $this->checkoutSession->expects($this->once())->method('start')->willReturnSelf();

        $json = [
            'SKU12323' => [
                'id' => 'SKU12323',
                'name' => 'Product Name',
                'price' => 116,
                'qty' => 2
            ]
        ];

        $this->jsonHelper->expects($this->once())->method('jsonEncode')->with($json)->willReturn('{encoded_string}');
        $this->assertEquals('{encoded_string}', $this->listJson->getCartContentForUpdate());
    }
}
