<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\ResourceModel\Index;

use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\TargetRule\Model\Cache\Index as IndexCache;
use Magento\TargetRule\Model\ResourceModel\Index\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var Index
     */
    private $model;

    /**
     * @var IndexCache|MockObject
     */
    private $cache;

    /**
     * @var int
     */
    private $type = 1;

    protected function setUp(): void
    {
        $this->cache = $this->getMockBuilder(IndexCache::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache->expects($this->any())
            ->method('getTag')
            ->willReturn('target_rule');
        $this->serializer = $this->getMockForAbstractClass(SerializerInterface::class);
        $this->model = (new ObjectManager($this))->getObject(
            \Magento\TargetRule\Model\ResourceModel\Index\Index::class,
            [
                'cache' => $this->cache,
                'type' => $this->type,
                'serializer' => $this->serializer,
            ]
        );
    }

    public function testLoadProductIdsBySegmentIdNotArray()
    {
        /** @var Product|MockObject $product */
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getEntityId')
            ->willReturn(2);

        /** @var \Magento\TargetRule\Model\Index|MockObject $indexModel */
        $indexModel = $this->getMockBuilder(\Magento\TargetRule\Model\Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $indexModel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $indexModel->expects($this->once())
            ->method('getStoreId')
            ->willReturn(3);
        $indexModel->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn(5);

        $this->cache->expects($this->once())
            ->method('load')
            ->with('target_rule_1_2_3_5_352')
            ->willReturn(null);

        $result = $this->model->loadProductIdsBySegmentId($indexModel, 352);

        $this->assertEquals([], $result);
    }

    public function testLoadProductIdsBySegmentIdString()
    {
        /** @var Product|MockObject $product */
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getEntityId')
            ->willReturn(2);

        /** @var \Magento\TargetRule\Model\Index|MockObject $indexModel */
        $indexModel = $this->getMockBuilder(\Magento\TargetRule\Model\Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $indexModel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $indexModel->expects($this->once())
            ->method('getStoreId')
            ->willReturn(3);
        $indexModel->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn(5);

        $serializedValue = 'serializedString';
        $expectedResult = [7, 9];
        $this->cache->expects($this->once())
            ->method('load')
            ->with('target_rule_1_2_3_5_352')
            ->willReturn($serializedValue);
        $this->serializer->expects($this->once())
            ->method('unserialize')
            ->with($serializedValue)
            ->willReturn($expectedResult);

        $result = $this->model->loadProductIdsBySegmentId($indexModel, 352);

        $this->assertEquals($expectedResult, $result);
    }

    public function testLoadProductIdsBySegmentIdArray()
    {
        /** @var Product|MockObject $product */
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getEntityId')
            ->willReturn(2);

        /** @var \Magento\TargetRule\Model\Index|MockObject $indexModel */
        $indexModel = $this->getMockBuilder(\Magento\TargetRule\Model\Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $indexModel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $indexModel->expects($this->once())
            ->method('getStoreId')
            ->willReturn(3);
        $indexModel->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn(5);

        $this->cache->expects($this->once())
            ->method('load')
            ->with('target_rule_1_2_3_5_352')
            ->willReturn([2, 5, 7]);
        $this->serializer->expects($this->never())
            ->method('unserialize');

        $result = $this->model->loadProductIdsBySegmentId($indexModel, 352);

        $this->assertEquals([2, 5, 7], $result);
    }

    public function testSaveResultForCustomerSegmentsNotSave()
    {
        /** @var Product|MockObject $product */
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getEntityId')
            ->willReturn(2);

        /** @var \Magento\TargetRule\Model\Index|MockObject $indexModel */
        $indexModel = $this->getMockBuilder(\Magento\TargetRule\Model\Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $indexModel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $indexModel->expects($this->once())
            ->method('getStoreId')
            ->willReturn(3);
        $indexModel->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $productIds = [7 => 0, 9 => 0];
        $serializedIds = '[7,9]';
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($productIds)
            ->willReturn($serializedIds);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $serializedIds,
                'target_rule_1_2_3_4_32',
                [
                    'target_rule_1_product_7',
                    'target_rule_1_product_9',
                    'target_rule_1_main_entity_2',
                    'target_rule_1_main_store_3',
                    'target_rule_1_main_customer_group_4',
                    'target_rule_1_main_customer_segment_32'
                ]
            )->willReturn(false);
        $this->cache->expects($this->once())
            ->method('remove')
            ->with('target_rule_1_2_3_4_32');

        $result = $this->model->saveResultForCustomerSegments($indexModel, 32, $productIds);

        $this->assertEquals($this->model, $result);
    }

    public function testSaveResultForCustomerSegmentsSave()
    {
        /** @var Product|MockObject $product */
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getEntityId')
            ->willReturn(2);

        /** @var \Magento\TargetRule\Model\Index|MockObject $indexModel */
        $indexModel = $this->getMockBuilder(\Magento\TargetRule\Model\Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $indexModel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $indexModel->expects($this->once())
            ->method('getStoreId')
            ->willReturn(3);
        $indexModel->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $productIds = [7 => 0, 9 => 0];
        $serializedIds = '[7,9]';
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($productIds)
            ->willReturn($serializedIds);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                $serializedIds,
                'target_rule_1_2_3_4_32',
                [
                    'target_rule_1_product_7',
                    'target_rule_1_product_9',
                    'target_rule_1_main_entity_2',
                    'target_rule_1_main_store_3',
                    'target_rule_1_main_customer_group_4',
                    'target_rule_1_main_customer_segment_32'
                ]
            )->willReturn(true);
        $this->cache->expects($this->never())
            ->method('remove');

        $result = $this->model->saveResultForCustomerSegments($indexModel, 32, $productIds);

        $this->assertEquals($this->model, $result);
    }

    public function testSaveResultForCustomerSegmentsEmpty()
    {
        /** @var Product|MockObject $product */
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method('getEntityId')
            ->willReturn(2);

        /** @var \Magento\TargetRule\Model\Index|MockObject $indexModel */
        $indexModel = $this->getMockBuilder(\Magento\TargetRule\Model\Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $indexModel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $indexModel->expects($this->once())
            ->method('getStoreId')
            ->willReturn(3);
        $indexModel->expects($this->once())
            ->method('getCustomerGroupId')
            ->willReturn(4);

        $this->cache->expects($this->once())
            ->method('remove')
            ->with('target_rule_1_2_3_4_32');

        $result = $this->model->saveResultForCustomerSegments($indexModel, 32, []);

        $this->assertEquals($this->model, $result);
    }

    public function testCleanIndexArray()
    {
        $this->cache->expects($this->once())
            ->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, ['target_rule_1_main_store_423']);

        $result = $this->model->cleanIndex([423]);

        $this->assertEquals($this->model, $result);
    }

    public function testCleanIndexObject()
    {
        /** @var Store|MockObject $store */
        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->expects($this->once())
            ->method('getId')
            ->willReturn(323);

        $this->cache->expects($this->once())
            ->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, ['target_rule_1_main_store_323']);

        $result = $this->model->cleanIndex($store);

        $this->assertEquals($this->model, $result);
    }

    public function testCleanIndexNull()
    {
        $this->cache->expects($this->once())
            ->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_ALL);

        $result = $this->model->cleanIndex(null);

        $this->assertEquals($this->model, $result);
    }

    public function testDeleteProductFromIndex()
    {
        $this->cache->expects($this->once())
            ->method('clean')
            ->with(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, ['target_rule_1_main_entity_3', 'target_rule_1_product_3']);

        $result = $this->model->deleteProductFromIndex(3);

        $this->assertEquals($this->model, $result);
    }

    public function testDeleteProductFromIndexNull()
    {
        $result = $this->model->deleteProductFromIndex(null);

        $this->assertEquals($this->model, $result);
    }
}
