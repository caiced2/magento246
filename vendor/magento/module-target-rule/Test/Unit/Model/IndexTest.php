<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Helper\Data;
use Magento\TargetRule\Model\ResourceModel\Index;
use Magento\TargetRule\Model\ResourceModel\Rule\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends TestCase
{
    /**
     * Tested model
     *
     * @var \Magento\TargetRule\Model\Index
     */
    protected $_index;

    /**
     * Store manager mock
     *
     * @var StoreManagerInterface|MockObject
     */
    protected $_storeManager;

    /**
     * Session mock
     *
     * @var \Magento\Customer\Model\Session|MockObject
     */
    protected $_session;

    /**
     * TargetRule data helper mock
     *
     * @var Data|MockObject
     */
    protected $_targetRuleData;

    /**
     * Index resource mock
     *
     * @var \Magento\TargetRule\Model\ResourceModel\Index|MockObject
     */
    protected $_resource;

    /**
     * Collection factory mock
     *
     * @var \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory|MockObject
     */
    protected $_collectionFactory;

    /**
     * Collection mock
     *
     * @var \Magento\TargetRule\Model\ResourceModel\Rule\Collection|MockObject
     */
    protected $_collection;

    protected function setUp(): void
    {
        $this->_storeManager = $this->_getCleanMock(StoreManagerInterface::class);
        $this->_session = $this->_getCleanMock(Session::class);
        $this->_targetRuleData = $this->_getCleanMock(Data::class);
        $this->_resource = $this->_getCleanMock(Index::class);
        $this->_collectionFactory = $this->createPartialMock(
            \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory::class,
            ['create']
        );

        $this->_collection = $this->createPartialMock(
            Collection::class,
            ['addApplyToFilter', 'addProductFilter', 'addIsActiveFilter', 'setPriorityOrder', 'setFlag']
        );
        $this->_collection->expects($this->any())
            ->method('addApplyToFilter')->willReturnSelf();

        $this->_collection->expects($this->any())
            ->method('addProductFilter')->willReturnSelf();

        $this->_collection->expects($this->any())
            ->method('addIsActiveFilter')->willReturnSelf();

        $this->_collection->expects($this->any())
            ->method('setPriorityOrder')->willReturnSelf();

        $this->_collection->expects($this->any())
            ->method('setFlag')->willReturnSelf();

        $this->_collectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->_collection);

        $this->_index = (new ObjectManager($this))->getObject(
            \Magento\TargetRule\Model\Index::class,
            [
                'context' => $this->_getCleanMock(Context::class),
                'registry' => $this->_getCleanMock(Registry::class),
                'ruleFactory' => $this->_collectionFactory,
                'storeManager' => $this->_storeManager,
                'session' => $this->_session,
                'targetRuleData' => $this->_targetRuleData,
                'resource' => $this->_resource,
                'resourceCollection' => $this->_getCleanMock(AbstractDb::class)
            ]
        );
    }

    /**
     * Get clean mock by class name
     *
     * @param string $className
     * @return MockObject
     */
    protected function _getCleanMock($className)
    {
        return $this->createMock($className);
    }

    public function testSetType()
    {
        $this->_index->setType(1);
        $this->assertEquals(1, $this->_index->getType());
    }

    /**
     * Test get type
     */
    public function testGetType()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage(
            'The Catalog Product List Type needs to be defined. Verify the type and try again.'
        );
        $this->_index->getType();
    }

    public function testSetStoreId()
    {
        $this->_index->setStoreId(1);
        $this->assertEquals(1, $this->_index->getStoreId());
    }

    public function testGetStoreId()
    {
        $store = $this->createPartialMock(Store::class, ['getId', '__wakeup']);

        $store->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $this->_storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($store);

        $this->assertEquals(2, $this->_index->getStoreId());
    }

    public function testSetCustomerGroupId()
    {
        $this->_index->setCustomerGroupId(1);
        $this->assertEquals(1, $this->_index->getCustomerGroupId());
    }

    public function testGetCustomerGroupId()
    {
        $this->_session->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(2);

        $this->assertEquals(2, $this->_index->getCustomerGroupId());
    }

    public function testSetLimit()
    {
        $this->_index->setLimit(1);
        $this->assertEquals(1, $this->_index->getLimit());
    }

    public function testGetLimit()
    {
        $this->_index->setType(1);

        $this->_targetRuleData->expects($this->any())
            ->method('getMaximumNumberOfProduct')
            ->willReturn(2);

        $this->assertEquals(2, $this->_index->getLimit());
    }

    public function testSetProduct()
    {
        $object = $this->_getCleanMock(DataObject::class);
        $this->_index->setProduct($object);
        $this->assertEquals($object, $this->_index->getProduct());
    }

    /**
     * Test getProduct
     */
    public function testGetProduct()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Please define a product data object.');
        $object = $this->getMockForAbstractClass(ProductInterface::class);
        $this->_index->setData('product', $object);
        $this->assertEquals($object, $this->_index->getProduct());
    }

    public function testSetExcludeProductIds()
    {
        $productIds = 1;
        $this->_index->setExcludeProductIds($productIds);
        $this->assertEquals([$productIds], $this->_index->getExcludeProductIds());

        $productIds = [1, 2];
        $this->_index->setExcludeProductIds($productIds);
        $this->assertEquals($productIds, $this->_index->getExcludeProductIds());
    }

    public function testGetExcludeProductIds()
    {
        $productIds = 1;
        $this->_index->setData('exclude_product_ids', $productIds);
        $this->assertEquals([], $this->_index->getExcludeProductIds());

        $productIds = [1, 2];
        $this->_index->setData('exclude_product_ids', $productIds);
        $this->assertEquals($productIds, $this->_index->getExcludeProductIds());
    }

    public function testGetProductIds()
    {
        $productIds = [1, 2];
        $this->_resource->expects($this->any())
            ->method('getProductIds')
            ->willReturn($productIds);

        $this->assertEquals($productIds, $this->_index->getProductIds());
    }

    public function testGetRuleCollection()
    {
        $this->_index->setType(1);
        $object = $this->_getCleanMock(DataObject::class);
        $this->_index->setData('product', $object);
        $this->assertEquals($this->_collection, $this->_index->getRuleCollection());
    }

    public function testSelect()
    {
        $select = $this->_getCleanMock(Select::class);
        $this->_resource->expects($this->any())
            ->method('select')
            ->willReturn($select);

        $this->assertEquals($select, $this->_index->select());
    }
}
