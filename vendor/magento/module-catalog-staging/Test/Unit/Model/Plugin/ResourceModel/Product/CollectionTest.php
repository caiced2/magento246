<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\ResourceModel\Product;

use Magento\CatalogStaging\Model\Plugin\ResourceModel\Product\Collection;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $metadataMock;

    /**
     * @var Collection
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $versionManagerMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = $this->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock;
        $metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->metadataMock);
        $this->model = $objectManager->getObject(
            Collection::class,
            [
                'metadataPool' => $metadataPoolMock,
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    public function testBeforeJoinAttribute()
    {
        $collectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $alias = 'test_alias';
        $attribute = 'catalog_product/weight_attribute';
        $bind = 'entity_id';
        $filter = 'test_filter';
        $joinType = 'test_join';
        $storeId = 1;

        $this->metadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn('row_id');

        $this->assertEquals(
            [$alias, $attribute, 'row_id', $filter, $joinType, $storeId],
            $this->model->beforeJoinAttribute(
                $collectionMock,
                $alias,
                $attribute,
                $bind,
                $filter,
                $joinType,
                $storeId
            )
        );
    }
}
