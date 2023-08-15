<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Block\Adminhtml\Manage\Accordion;

use Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Rcompared;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Customer\Model\Customer;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Helper\Admin;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Rcompared
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RcomparedTest extends TestCase
{
    /**
     * @var Rcompared
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $compareList;

    /**
     * @var MockObject
     */
    protected $itemCollection;

    /**
     * @var MockObject
     */
    protected $listCompareFactory;

    /**
     * @var MockObject
     */
    protected $registry;

    /**
     * @var int
     */
    protected $storeId = 1;

    /**
     * @var int
     */
    protected $customerId = 1;

    /**
     * @var MockObject
     */
    protected $productListFactory;

    /**
     * @var MockObject
     */
    protected $productCollection;

    /**
     * @var MockObject
     */
    protected $stockRegistry;

    protected function setUp(): void
    {
        $this->itemCollection =
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();

        $this->listCompareFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->listCompareFactory->expects($this->any())->method('create')
            ->willReturn($this->itemCollection);

        $customer = $this->createMock(Customer::class);
        $customer->expects($this->any())->method('getId')->willReturn($this->customerId);
        $store = $this->createMock(Store::class);
        $store->expects($this->any())->method('getId')->willReturn($this->storeId);

        $this->registry = $this->createRegistryMock([
            'checkout_current_customer' => $customer,
            'checkout_current_store'    => $store,
        ]);

        $this->productCollection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->productListFactory =
            $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();
        $this->productListFactory->expects($this->any())->method('create')
            ->willReturn($this->productCollection);

        $this->stockRegistry = $this->getMockBuilder(StockRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStockItem'])
            ->getMock();
    }

    /**
     * Create registry mock
     *
     * @param array $registryData
     * @return MockObject
     */
    protected function createRegistryMock($registryData)
    {
        $coreRegistry = $this->createMock(Registry::class);
        $registryCallback = $this->returnCallback(function ($key) use ($registryData) {
            return $registryData[$key];
        });
        $coreRegistry->expects($this->any())->method('registry')->will($registryCallback);
        return $coreRegistry;
    }

    /**
     * Create mocks of product
     *
     * @return array
     */
    protected function createMocksOfProduct()
    {
        $firstProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'isInStock'])
            ->getMock();
        $firstProduct->expects($this->any())->method('getId')->willReturn(2);
        $firstProduct->expects($this->any())->method('isInStock')->willReturn(true);

        $secondProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'isInStock'])
            ->getMock();
        $secondProduct->expects($this->any())->method('getId')->willReturn(3);
        $secondProduct->expects($this->any())->method('isInStock')->willReturn(false);

        $this->productCollection->expects($this->once())->method('removeItemByKey')->with(3);

        $stockItem = $this->createPartialMock(
            Item::class,
            ['getIsInStock']
        );
        $stockItem->expects($this->any())
            ->method('getIsInStock')
            ->willReturn(true);

        $this->stockRegistry->expects($this->any())
            ->method('getStockItem')
            ->willReturn($stockItem);

        return [$firstProduct, $secondProduct];
    }

    public function testItemsCollectionGetter()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->itemCollection->expects($this->once())->method('useProductItem')->willReturnSelf();
        $this->itemCollection->expects($this->once())->method('setStoreId')->with($this->storeId)->willReturnSelf();
        $this->itemCollection->expects($this->once())->method('addStoreFilter')->with($this->storeId)->willReturnSelf();
        $this->itemCollection->expects($this->once())->method('setCustomerId')->with($this->customerId)->willReturnSelf(
        );
        $this->itemCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([]));

        $catalogConfig = $this->createMock(Config::class);
        $catalogConfig->expects($this->any())->method('getProductAttributes')->willReturn([]);

        $this->productCollection->expects($this->once())->method('setStoreId')->with($this->storeId)->willReturnSelf();
        $this->productCollection->expects($this->once())->method('addStoreFilter')->with(
            $this->storeId
        )->willReturnSelf();
        $this->productCollection->expects($this->once())->method('addAttributeToSelect')->with(
            ['status']
        )->willReturnSelf();
        $this->productCollection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator($this->createMocksOfProduct()));
        $this->productCollection->expects($this->once())->method('addOptionsToResult')->willReturnSelf();
        $this->productCollection->method('getItems')->willReturn($this->createMocksOfProduct());

        $adminhtmlSales = $this->createMock(Admin::class);
        $adminhtmlSales->expects($this->once())->method('applySalableProductTypesFilter')
            ->willReturn($this->productCollection);

        $this->model = $objectManagerHelper->getObject(
            Rcompared::class,
            [
                'compareListFactory' => $this->listCompareFactory,
                'coreRegistry' => $this->registry,
                'catalogConfig'      => $catalogConfig,
                'productListFactory' => $this->productListFactory,
                'adminhtmlSales'     => $adminhtmlSales,
                'stockRegistry'      => $this->stockRegistry
            ]
        );
        $this->assertSame($this->productCollection, $this->model->getData('items_collection'));
    }
}
