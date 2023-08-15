<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Registry;
use Magento\MultipleWishlist\Helper\Data;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Model\ResourceModel\Item\Collection as ItemCollection;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as ItemCollectionFactory;
use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends TestCase
{
    /** @var  Data */
    protected $model;

    /** @var  Context|MockObject */
    protected $context;

    /** @var  Registry|MockObject */
    protected $registry;

    /** @var  CustomerInterface|MockObject */
    protected $customerData;

    /** @var  CustomerSession|MockObject */
    protected $customerSession;

    /** @var  Wishlist|MockObject */
    protected $wishlist;

    /** @var  WishlistFactory|MockObject */
    protected $wishlistFactory;

    /** @var  StoreManagerInterface|MockObject */
    protected $storeManager;

    /** @var  PostHelper|MockObject */
    protected $postHelper;

    /** @var  CustomerViewHelper|MockObject */
    protected $customerViewHelper;

    /** @var  WishlistProviderInterface|MockObject */
    protected $wishlistProvider;

    /** @var  ProductRepositoryInterface|MockObject */
    protected $productRepository;

    /** @var  ItemCollection|MockObject */
    protected $itemCollection;

    /** @var  ItemCollectionFactory|MockObject */
    protected $itemCollectionFactory;

    /** @var  Collection|MockObject */
    protected $collection;

    /** @var  CollectionFactory|MockObject */
    protected $collectionFactory;

    /** @var  ModuleManager|MockObject */
    protected $moduleManager;

    /** @var  ScopeConfigInterface|MockObject */
    protected $scopeConfig;

    protected function setUp(): void
    {
        $this->prepareContext();
        $this->prepareWishlist();
        $this->prepareCustomer();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMockForAbstractClass();

        $this->postHelper = $this->getMockBuilder(PostHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerViewHelper = $this->getMockBuilder(\Magento\Customer\Helper\View::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->model = new Data(
            $this->context,
            $this->registry,
            $this->customerSession,
            $this->wishlistFactory,
            $this->storeManager,
            $this->postHelper,
            $this->customerViewHelper,
            $this->wishlistProvider,
            $this->productRepository,
            $this->itemCollectionFactory,
            $this->collectionFactory
        );
    }

    protected function prepareContext()
    {
        $this->moduleManager = $this->getMockBuilder(\Magento\Framework\Module\Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods([
                'getValue',
            ])
            ->getMockForAbstractClass();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->any())
            ->method('getModuleManager')
            ->willReturn($this->moduleManager);
        $this->context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfig);
    }

    protected function prepareWishlist()
    {
        $this->wishlist = $this->getMockBuilder(Wishlist::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'loadByCustomerId',
                'getCustomerId',
                'getId',
                'getItemCollection',
                'setCustomerId',
                'generateSharingCode',
                'save',
            ])
            ->getMock();

        $this->wishlistFactory = $this->getMockBuilder(WishlistFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
            ])
            ->getMock();

        $this->wishlistFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->wishlist);

        $this->wishlistProvider = $this->getMockBuilder(WishlistProviderInterface::class)
            ->getMockForAbstractClass();

        $this->itemCollection = $this->getMockBuilder(\Magento\Wishlist\Model\ResourceModel\Item\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemCollectionFactory = $this->createMock(
            \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory::class
        );

        $this->collection = $this->getMockBuilder(\Magento\Wishlist\Model\ResourceModel\Wishlist\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory = $this->createPartialMock(
            \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory::class,
            ['create']
        );

        $this->collectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->collection);
    }

    protected function prepareCustomer()
    {
        $this->customerData = $this->getMockBuilder(CustomerInterface::class)
            ->getMockForAbstractClass();

        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param bool $generalActive
     * @param bool $generalMultipleEnabled
     * @param bool $isOutputEnabled
     * @param bool $expectedResult
     * @dataProvider dataProviderIsMultipleEnabled
     */
    public function testIsMultipleEnabled(
        $generalActive,
        $generalMultipleEnabled,
        $isOutputEnabled,
        $expectedResult
    ) {
        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                ['wishlist/general/active', ScopeInterface::SCOPE_STORE, null, $generalActive],
                ['wishlist/general/multiple_enabled', ScopeInterface::SCOPE_STORE, null, $generalMultipleEnabled],
            ]);

        $this->moduleManager->expects($this->once())
            ->method('isOutputEnabled')
            ->with('Magento_MultipleWishlist')
            ->willReturn($isOutputEnabled);

        $this->assertEquals($expectedResult, $this->model->isMultipleEnabled());
    }

    /**
     * 1. Wishlist general active flag
     * 2. Wishlist general multiple_enabled flag
     * 3. Is module output enabled flag
     * 4. Expected result
     *
     * @return array
     */
    public function dataProviderIsMultipleEnabled()
    {
        return [
            [false, false, false, false],
            [false, true, false, false],
            [false, true, true, false],
            [false, true, true, false],
            [true, false, true, false],
            [true, false, false, false],
            [true, true, false, false],
            [true, true, true, true],
        ];
    }

    /**
     * @param int $customerId
     * @param bool $isLoggedIn
     * @dataProvider dataProviderGetDefaultWishlist
     */
    public function testGetDefaultWishlist(
        $customerId,
        $resultCustomerId,
        $isLoggedIn
    ) {
        $this->customerData->expects($this->any())
            ->method('getId')
            ->willReturn($resultCustomerId);

        $this->customerSession->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn($isLoggedIn);
        $this->customerSession->expects($this->any())
            ->method('getCustomerDataObject')
            ->willReturn($this->customerData);

        $this->wishlist->expects($this->once())
            ->method('loadByCustomerId')
            ->willReturnMap([
                [$resultCustomerId, false, $this->wishlist],
                [$customerId, false, $this->wishlist],
            ]);

        $this->assertEquals($this->wishlist, $this->model->getDefaultWishlist());
    }

    /**
     * 1. Customer ID
     * 2. Result Customer ID
     * 3. Customer "logged in" flag
     *
     * @return array
     */
    public function dataProviderGetDefaultWishlist()
    {
        return [
            [null, 1, true],
            [null, null, false],
            [1, 1, false],
            [1, 1, true],
        ];
    }

    public function testIsWishlistDefault()
    {
        $customerId = 1;
        $wishlistId = 1;

        $this->wishlist->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);
        $this->wishlist->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($wishlistId);

        $this->model->isWishlistDefault($this->wishlist);
    }

    public function testGetWishlistLimit()
    {
        $limit = 1;

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with('wishlist/general/multiple_wishlist_number', ScopeInterface::SCOPE_STORE)
            ->willReturn($limit);

        $this->assertEquals($limit, $this->model->getWishlistLimit());
    }

    public function testIsWishlistLimitReached()
    {
        $limit = 1;

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with('wishlist/general/multiple_wishlist_number', ScopeInterface::SCOPE_STORE)
            ->willReturn($limit);

        $this->assertFalse($this->model->isWishlistLimitReached($this->collection));
    }

    /**
     * @param int $qty
     * @param int $size
     * @param bool $useQty
     * @param int $expectedResult
     * @dataProvider dataProviderGetWishlistItemCount
     */
    public function testGetWishlistItemCount(
        $qty,
        $size,
        $useQty,
        $expectedResult
    ) {
        $this->wishlist->expects($this->once())
            ->method('getItemCollection')
            ->willReturn($this->itemCollection);

        $this->itemCollection->expects($this->any())
            ->method('setInStockFilter')
            ->with(true)
            ->willReturnSelf();
        $this->itemCollection->expects($this->any())
            ->method('getItemsQty')
            ->willReturn($qty);
        $this->itemCollection->expects($this->any())
            ->method('getSize')
            ->willReturn($size);

        $this->scopeConfig->expects($this->any())
            ->method('getValue')
            ->with(Data::XML_PATH_WISHLIST_LINK_USE_QTY, ScopeInterface::SCOPE_STORE)
            ->willReturn($useQty);

        $this->assertEquals($expectedResult, $this->model->getWishlistItemCount($this->wishlist));
    }

    /**
     * 1. Qty
     * 2. Size
     * 3. Use Qty flag
     * 4. Expected result
     *
     * @return array
     */
    public function dataProviderGetWishlistItemCount()
    {
        return [
            [1, 2, true, 1],
            [1, 2, false, 2],
        ];
    }

    /**
     * @param int $customerId
     * @param int $resultCustomerId
     * @param bool $isLoggedIn
     * @dataProvider dataProviderGetCustomerWishlists
     */
    public function testGetCustomerWishlists(
        $customerId,
        $resultCustomerId,
        $isLoggedIn
    ) {
        $this->customerData->expects($this->any())
            ->method('getId')
            ->willReturn($resultCustomerId);

        $this->customerSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn($isLoggedIn);
        $this->customerSession->expects($this->any())
            ->method('getCustomerDataObject')
            ->willReturn($this->customerData);

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('wishlists_by_customer')
            ->willReturn([$resultCustomerId => $this->collection]);

        $this->assertEquals($this->collection, $this->model->getCustomerWishlists($customerId));
    }

    /**
     * 1. Customer ID
     * 2. Result Customer ID
     * 3. Customer "logged in" flag
     *
     * @return array
     */
    public function dataProviderGetCustomerWishlists()
    {
        return [
            [null, 1, true],
            [null, null, false],
            [1, 1, false],
            [1, 1, true],
        ];
    }

    public function testGetCustomerWishlistsHasCollection()
    {
        $customerId = 1;

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('wishlists_by_customer')
            ->willReturn(null);
        $this->registry->expects($this->any())
            ->method('register')
            ->with('wishlists_by_customer', [$customerId => $this->collection])
            ->willReturnSelf();

        $this->collection->expects($this->any())
            ->method('filterByCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->collection->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->wishlist]);

        $this->assertEquals($this->collection, $this->model->getCustomerWishlists($customerId));
    }

    public function testGetCustomerWishlistsHasCollectionNoItems()
    {
        $customerId = 1;

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('wishlists_by_customer')
            ->willReturn(null);
        $this->registry->expects($this->any())
            ->method('register')
            ->with('wishlists_by_customer', [$customerId => $this->collection])
            ->willReturnSelf();

        $this->collection->expects($this->any())
            ->method('filterByCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->collection->expects($this->any())
            ->method('getItems')
            ->willReturn(null);
        $this->collection->expects($this->any())
            ->method('addItem')
            ->with($this->wishlist)
            ->willReturnSelf();

        $this->wishlist->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('generateSharingCode')
            ->willReturnSelf();
        $this->wishlist->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->assertEquals($this->collection, $this->model->getCustomerWishlists($customerId));
    }
}
