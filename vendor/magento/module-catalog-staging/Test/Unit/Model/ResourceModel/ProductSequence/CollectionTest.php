<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\ResourceModel\ProductSequence;

use Magento\CatalogStaging\Model\ResourceModel\ProductSequence\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Sequence\SequenceRegistry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testDeleteSequence()
    {
        $objectManager = new ObjectManager($this);
        $metadataPoolMock = $this->createMock(MetadataPool::class);
        $resourceMock = $this->createMock(ResourceConnection::class);
        $sequenceRegistryMock = $this->createMock(
            SequenceRegistry::class
        );
        $metadataMock = $this->createMock(EntityMetadata::class);
        $metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadataMock);
        $connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $sequenceRegistryMock->expects($this->once())
            ->method('retrieve')
            ->willReturn(['sequenceTable' => 'sequence_table']);
        $metadataMock->expects($this->once())
            ->method('getEntityConnection')
            ->willReturn($connectionMock);
        /** @var Collection $model */
        $model = $objectManager->getObject(
            Collection::class,
            [
                'metadataPool' => $metadataPoolMock,
                'resource' => $resourceMock,
                'sequenceRegistry' => $sequenceRegistryMock
            ]
        );
        $resourceMock->expects($this->once())
            ->method('getTableName')
            ->with('sequence_table')
            ->willReturn('sequence_table');
        $ids = [1, 2, 3];
        $connectionMock->expects($this->once())
            ->method('delete')
            ->with('sequence_table', ['sequence_value IN (?)' => $ids]);
        $model->deleteSequence($ids);
    }
}
