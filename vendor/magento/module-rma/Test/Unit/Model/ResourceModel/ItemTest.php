<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\ResourceModel;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\ResourceModel\Helper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\Locale\Format;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\ResourceModel\Item as RmaItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemTest extends TestCase
{
    /**
     * @var RmaItem
     */
    protected $resourceModel;

    /**
     * @var MockObject
     */
    protected $appResource;

    /**
     * @var MockObject
     */
    protected $eqvModelConfig;

    /**
     * @var MockObject
     */
    protected $attributeSet;

    /**
     * @var MockObject
     */
    protected $formatLocale;

    /**
     * @var MockObject
     */
    protected $resourceHelper;

    /**
     * @var MockObject
     */
    protected $validatorFactory;

    /**
     * @var MockObject
     */
    protected $rmaHelper;

    /**
     * @var MockObject
     */
    protected $orderItemCollection;

    /**
     * @var MockObject
     */
    protected $productFactory;

    /**
     * @var MockObject
     */
    protected $productTypesConfig;

    /**
     * @var MockObject
     */
    protected $adminItem;

    /**
     * @var ProductCollectionFactory|MockObject
     */
    private $productCollectionFactoryMock;

    /**
     * @var Product|MockObject
     */
    private $productMock;

    protected function setUp(): void
    {
        $this->appResource = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eqvModelConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeSet = $this->getMockBuilder(Set::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatLocale = $this->getMockBuilder(Format::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceHelper = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorFactory = $this->getMockBuilder(UniversalFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rmaHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderItemCollection =
            $this->getMockBuilder(
                \Magento\Rma\Model\ResourceModel\Order\Item\CollectionFactory::class
            )
                ->setMethods(['create'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->productFactory = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productTypesConfig = $this->getMockBuilder(\Magento\Catalog\Model\ProductTypes\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adminItem = $this->getMockBuilder(\Magento\Sales\Model\Order\Admin\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = [];
        $this->productCollectionFactoryMock = $this->getMockBuilder(ProductCollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $arguments = [
            'resource' => $this->appResource,
            'eavConfig' => $this->eqvModelConfig,
            'attrSetEntity' => $this->attributeSet,
            'localeFormat' => $this->formatLocale,
            'resourceHelper' => $this->resourceHelper,
            'universalFactory' => $this->validatorFactory,
            'rmaData' => $this->rmaHelper,
            'ordersFactory' => $this->orderItemCollection,
            'productFactory' => $this->productFactory,
            'refundableList' => $this->productTypesConfig,
            'adminOrderItem' => $this->adminItem,
            'data' => $data,
            'productCollectionFactory' => $this->productCollectionFactoryMock
        ];

        $this->resourceModel = $objectManager->getObject(RmaItem::class, $arguments);
    }

    public function testGetReturnableItems()
    {
        $shippedItems = [5 => 3];
        $expectsItems = [5 => 0];
        $salesAdapterMock = $this->getAdapterMock($shippedItems);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);
        $orderId = 1000001;
        $result = $this->resourceModel->getReturnableItems($orderId);
        $this->assertEquals($expectsItems, $result);
    }

    public function testGetOrderItemsNoItems()
    {
        $orderId = 10000001;

        $readMock = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $readMock->expects($this->any())
            ->method('quoteIdentifier')
            ->willReturnArgument(0);

        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($readMock);
        $expression = new \Zend_Db_Expr('(qty_shipped - qty_returned)');

        $orderItemsCollectionMock = $this->getMockBuilder(
            \Magento\Rma\Model\ResourceModel\Order\Item\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemsCollectionMock->expects($this->once())
            ->method('addExpressionFieldToSelect')
            ->with('available_qty', $expression, ['qty_shipped', 'qty_returned'])->willReturnSelf();
        $orderItemsCollectionMock->expects($this->any())
            ->method('addFieldToFilter')->willReturnSelf();
        $orderItemsCollectionMock->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->willReturn($orderItemsCollectionMock);

        $result = $this->resourceModel->getOrderItems($orderId);
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    public function testGetOrderItemsRemoveByParent()
    {
        $orderId = 10000001;
        $excludeId = 5;
        $parentId = 6;
        $itemId = 1;

        $readMock = $this->getAdapterMock([$itemId => 1]);
        $salesAdapterMock = $this->getAdapterMock([$itemId => 1]);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);
        $this->resourceModel->setConnection($readMock);

        $orderItemsCollectionMock = $this->prepareOrderItemCollectionMock(0);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->willReturn($orderItemsCollectionMock);

        $parentItemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParentItemId', 'getId'])
            ->getMock();
        $parentItemMock->expects($this->any())
            ->method('getId')
            ->willReturn($itemId);
        $parentItemMock->expects($this->any())
            ->method('getParentItemId')
            ->willReturn($parentId);

        $iterator = new \ArrayIterator([$parentItemMock]);

        $orderItemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);

        $result = $this->resourceModel->getOrderItems($orderId, $excludeId);
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    /**
     * @return void
     */
    public function testGetOrderItemsCanReturnNotEmpty(): void
    {
        $orderId = 10000001;
        $itemId = 1;
        $fetchData = [$itemId => 2];
        $storeId = 1;

        $salesAdapterMock = $this->getAdapterMock($fetchData);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);

        $orderItemsCollectionMock = $this->prepareOrderItemCollectionMock(1);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->willReturn($orderItemsCollectionMock);

        $itemMockCanReturn = $this->prepareOrderItemMock($itemId, $storeId, $this->productMock);

        $iterator = new \ArrayIterator([$itemMockCanReturn]);

        $orderItemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);

        $this->rmaHelper->method('canReturnProduct')
            ->willReturn(true);

        $this->prepareProductCollectionMock([1 => $this->productMock]);

        $returnableItems = $this->resourceModel->getReturnableItems($orderId);
        $result = $this->resourceModel->getOrderItems($orderId);
        foreach ($result as $item) {
            $this->assertEquals($item->getAvailableQty(), $returnableItems[$item->getId()]);
        }
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    /**
     * @return void
     */
    public function testGetOrderItemsCanReturnEmpty(): void
    {
        $orderId = 10000001;
        $itemId = 1;
        $fetchData = [];
        $storeId = 1;

        $salesAdapterMock = $this->getAdapterMock($fetchData);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);

        $orderItemsCollectionMock = $this->prepareOrderItemCollectionMock(1);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->willReturn($orderItemsCollectionMock);

        $this->productFactory->method('create')
            ->willReturn($this->productMock);

        $this->rmaHelper->method('canReturnProduct')
            ->willReturn(true);

        $this->prepareProductCollectionMock([1 => $this->productMock]);

        $itemMockCanReturn = $this->prepareOrderItemMock($itemId, $storeId, $this->productMock);

        $iterator = new \ArrayIterator([$itemMockCanReturn]);

        $orderItemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);

        $result = $this->resourceModel->getOrderItems($orderId);
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    /**
     * @return void
     */
    public function testGetOrderItemsCanReturn(): void
    {
        $orderId = 10000001;
        $itemId = 1;
        $fetchData = [];
        $storeId = 1;

        $salesAdapterMock = $this->getAdapterMock($fetchData);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);

        $orderItemsCollectionMock = $this->prepareOrderItemCollectionMock(1);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->willReturn($orderItemsCollectionMock);

        $this->productFactory->method('create')
            ->willReturn($this->productMock);

        $itemMockCanReturn = $this->prepareOrderItemMock($itemId, $storeId, $this->productMock);

        $iterator = new \ArrayIterator([$itemMockCanReturn]);

        $orderItemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);

        $this->rmaHelper->method('canReturnProduct')
            ->willReturn(true);

        $this->prepareProductCollectionMock([1 => $this->productMock]);

        $result = $this->resourceModel->getOrderItems($orderId);
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    /**
     * @return void
     */
    public function testGetOrderItemsCanReturnNoItems(): void
    {
        $orderId = 10000001;
        $itemId = 1;
        $fetchData = [];
        $storeId = 1;

        $salesAdapterMock = $this->getAdapterMock($fetchData);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);

        $orderItemsCollectionMock = $this->prepareOrderItemCollectionMock(1);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->willReturn($orderItemsCollectionMock);

        $this->productFactory->method('create')
            ->willReturn($this->productMock);

        $itemMockCanReturn = $this->prepareOrderItemMock($itemId, $storeId, $this->productMock);

        $iterator = new \ArrayIterator([$itemMockCanReturn]);

        $orderItemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn($iterator);

        $this->prepareProductCollectionMock([]);

        $result = $this->resourceModel->getOrderItems($orderId);
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    /**
     * Get universal adapter mock with specified result for fetchPairs
     *
     * @param array $data
     * @return MockObject
     */
    protected function getAdapterMock($data)
    {
        $this->appResource->expects($this->any())
            ->method('getTableName')
            ->willReturnArgument(0);

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('from')->willReturnSelf();
        $selectMock->expects($this->any())
            ->method('joinInner')->willReturnSelf();
        $selectMock->expects($this->any())
            ->method('where')->willReturnSelf();

        $connectionMock = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($selectMock);
        $connectionMock->expects($this->any())
            ->method('fetchPairs')
            ->willReturn($data);

        return $connectionMock;
    }

    /**
     * @param int $itemId
     * @param int $storeId
     * @param Product|MockObject $productMock
     * @return MockObject
     */
    protected function prepareOrderItemMock($itemId, $storeId, $productMock)
    {
        $itemMockCanReturn = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getParentItemId',
                    'getId',
                    '__wakeup',
                    'getStoreId',
                    'getProduct',
                    'getOrderProducts',
                    'getProductId'
                ]
            )
            ->getMock();
        $itemMockCanReturn->expects($this->any())
            ->method('getId')
            ->willReturn($itemId);
        $itemMockCanReturn->method('getStoreId')
            ->willReturn($storeId);
        $itemMockCanReturn->method('getProduct')
            ->willReturn($productMock);
        $itemMockCanReturn->method('getProductId')
            ->willReturn(1);

        return $itemMockCanReturn;
    }

    /**
     * @param int $countItemCollection
     *
     * @return MockObject|\Magento\Rma\Model\ResourceModel\Order\Item\Collection
     */
    protected function prepareOrderItemCollectionMock(int $countItemCollection)
    {
        $orderItemsCollectionMock = $this->getMockBuilder(
            \Magento\Rma\Model\ResourceModel\Order\Item\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemsCollectionMock->expects($this->once())
            ->method('addExpressionFieldToSelect')->willReturnSelf();
        $orderItemsCollectionMock->expects($this->any())
            ->method('addFieldToFilter')->willReturnSelf();
        $orderItemsCollectionMock->expects($this->once())
            ->method('count')
            ->willReturn($countItemCollection);
        $orderItemsCollectionMock->expects($this->any())
            ->method('removeItemByKey');
        return $orderItemsCollectionMock;
    }

    /**
     * Prepare product collection mocks for "getOrderProducts" method
     *
     * @param array $items
     * @return void
     */
    private function prepareProductCollectionMock(array $items): void
    {
        $productCollectionMock = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productCollectionFactoryMock->method('create')
            ->willReturn($productCollectionMock);

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productCollectionMock->method('getSelect')
            ->willReturn($selectMock);

        $productCollectionMock->method('getItems')
            ->willReturn($items);

        $selectMock->method('reset')->willReturnSelf();
        $selectMock->method('columns')->willReturnSelf();
    }
}
