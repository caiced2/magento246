<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Block\Adminhtml\Manage\Accordion;

use Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Wishlist;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Wishlist\Model\ResourceModel\Item\Collection;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Wishlist accordion test
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WishlistTest extends TestCase
{
    /** @var Wishlist */
    protected $wishlist;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Context|MockObject */
    protected $contextMock;

    /** @var Registry|MockObject */
    protected $registryMock;

    /** @var MockObject */
    protected $itemCollectionMock;

    /** @var MockObject */
    protected $stockItemMock;

    /**
     * @var MockObject
     */
    protected $stockRegistry;

    protected function setUp(): void
    {
        $writeInterface = $this->getMockForAbstractClass(WriteInterface::class);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('getDirectoryWrite')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($writeInterface);

        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->expects($this->once())
            ->method('getFilesystem')
            ->willReturn($filesystem);

        $this->registryMock = $this->createMock(Registry::class);

        $this->itemCollectionMock = $this->createMock(
            Collection::class
        );
        $itemCollFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $itemCollFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->itemCollectionMock);

        $this->stockItemMock = $this->createPartialMock(
            Item::class,
            ['getIsInStock']
        );
        $this->stockItemMock->expects($this->any())
            ->method('getIsInStock')
            ->willReturn(true);

        $this->stockRegistry = $this->getMockBuilder(StockRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStockItem'])
            ->getMock();
        $this->stockRegistry->expects($this->any())
            ->method('getStockItem')
            ->willReturn($this->stockItemMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->wishlist = $this->objectManagerHelper->getObject(
            Wishlist::class,
            [
                'context' => $this->contextMock,
                'coreRegistry' => $this->registryMock,
                'itemFactory' => $itemCollFactory,
                'stockRegistry' => $this->stockRegistry
            ]
        );
    }

    public function testGetItemsCollection()
    {
        $customerId = 2;
        $customer = $this->createMock(Customer::class);
        $customer->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $storeIds = [1, 2, 3];
        $website = $this->createMock(Website::class);
        $website->expects($this->once())
            ->method('getStoreIds')
            ->willReturn($storeIds);

        $store = $this->createMock(Store::class);
        $store->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);

        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturnMap(
                [
                    ['checkout_current_customer', $customer],
                    ['checkout_current_store', $store],
                ]
            );

        $this->itemCollectionMock->expects($this->once())
            ->method('addCustomerIdFilter')
            ->with($customerId)->willReturnSelf();

        $this->itemCollectionMock->expects($this->once())
            ->method('addStoreFilter')
            ->with($storeIds)->willReturnSelf();

        $this->itemCollectionMock->expects($this->once())
            ->method('setVisibilityFilter')->willReturnSelf();

        $this->itemCollectionMock->expects($this->once())
            ->method('setSalableFilter')->willReturnSelf();

        $this->itemCollectionMock->expects($this->once())
            ->method('resetSortOrder')->willReturnSelf();

        $this->prepareItemListMock();

        $this->assertNull($this->wishlist->getData('items_collection'));
        $this->assertSame($this->itemCollectionMock, $this->wishlist->getItemsCollection());
        $this->assertSame($this->itemCollectionMock, $this->wishlist->getData('items_collection'));
        // lazy load test
        $this->assertSame($this->itemCollectionMock, $this->wishlist->getItemsCollection());
    }

    protected function prepareItemListMock()
    {
        $itemList = new \ArrayIterator(
            [
                $this->getWishlistItemMock(1, ['is_product' => false]),
                $this->getWishlistItemMock(
                    2,
                    [
                        'is_product' => true,
                        'product_id' => 22,
                        'service_stock' => false,
                        'product_stock' => true,
                    ]
                ),
                $this->getWishlistItemMock(
                    3,
                    [
                        'is_product' => true,
                        'product_id' => 33,
                        'service_stock' => true,
                        'product_stock' => false,
                    ]
                ),
                $this->getWishlistItemMock(
                    4,
                    [
                        'is_product' => true,
                        'product_id' => 44,
                        'service_stock' => true,
                        'product_stock' => true,
                        'product_name' => 'Product Name',
                        'product_price' => 'Product Price',
                    ]
                ),
            ]
        );

        $this->stockItemMock->expects($this->any())
            ->method('getIsInStock')
            ->willReturnMap(
                [
                    [22, false],
                    [33, true],
                    [44, true],
                ]
            );
        $this->itemCollectionMock->expects($this->any())
            ->method('removeItemByKey')
            ->willReturnMap(
                [
                    [2, $this->itemCollectionMock],
                    [3, $this->itemCollectionMock],
                ]
            );

        $this->itemCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn($itemList);
    }

    /**
     * @param int $itemId
     * @param array $config
     * @return MockObject
     */
    protected function getWishlistItemMock($itemId, $config)
    {
        $item = $this->getMockBuilder(\Magento\Wishlist\Model\ResourceModel\Item::class)->addMethods(
            ['getId', 'setName', 'setPrice', 'getProduct']
        )
            ->disableOriginalConstructor()
            ->getMock();

        if ($config['is_product']) {
            $product = $this->createPartialMock(
                Product::class,
                ['getId', 'getName', 'getPrice', 'isInStock']
            );

            $item->expects($this->once())
                ->method('getProduct')
                ->willReturn($product);

            $product->expects($this->once())
                ->method('getId')
                ->willReturn($config['product_id']);

            if (!$config['service_stock'] || !$config['product_stock']) {
                $item->expects($this->once())
                    ->method('getId')
                    ->willReturn($itemId);
            }

            if ($config['service_stock']) {
                $product->expects($this->once())
                    ->method('isInStock')
                    ->willReturn($config['product_stock']);
            }
            if ($config['service_stock'] && $config['product_stock']) {
                $product->expects($this->once())
                    ->method('getName')
                    ->willReturn($config['product_name']);

                $product->expects($this->once())
                    ->method('getPrice')
                    ->willReturn($config['product_price']);

                $item->expects($this->once())
                    ->method('setName')
                    ->with($config['product_name'])->willReturnSelf();
                $item->expects($this->once())
                    ->method('setPrice')
                    ->with($config['product_price'])->willReturnSelf();
            }
        } else {
            $item->expects($this->once())
                ->method('getProduct')
                ->willReturn(false);
        }
        return $item;
    }
}
