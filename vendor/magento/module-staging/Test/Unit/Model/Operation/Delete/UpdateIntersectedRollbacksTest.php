<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Operation\Delete;

use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Model\AbstractModel;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Operation\Delete\UpdateIntersectedRollbacks;
use Magento\Staging\Model\Operation\Update\UpdateEntityVersion;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateIntersectedRollbacksTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $readEntityVersionMock;

    /**
     * @var MockObject
     */
    private $versionManagerMock;

    /**
     * UpdateIntersectedRollbacks
     */
    private $model;

    /**
     * @var MockObject
     */
    private $entityMock;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPool;

    /**
     * @var HydratorPool|MockObject
     */
    private $hydratorPool;

    /**
     * @var UpdateEntityVersion|MockObject
     */
    private $updateEntityVersion;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->entityType = '\TestModule\Api\Data\TestModuleInterface';
        $this->readEntityVersionMock =
            $this->createMock(ReadEntityVersion::class);
        $this->versionManagerMock =
            $this->createMock(VersionManager::class);
        $typeResolverMock = $this->createMock(TypeResolver::class);
        $typeResolverMock->expects($this->any())
            ->method('resolve')
            ->willReturn($this->entityType);
        $this->entityMock = $this->getMockBuilder(AbstractModel::class)
            ->addMethods(['setCreatedIn', 'setUpdatedIn', 'setRowId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hydratorPool = $this->getMockBuilder(HydratorPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateEntityVersion = $this->getMockBuilder(UpdateEntityVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new UpdateIntersectedRollbacks(
            $typeResolverMock,
            $this->metadataPool,
            $this->hydratorPool,
            $this->readEntityVersionMock,
            $this->versionManagerMock,
            $this->updateEntityVersion
        );
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $versionId = 3;
        $endVersionId = 2147483647;
        $entityId = 1;
        $createdIn = 10;
        $rollbackId = 7;
        $rowId = 2;
        $nextVersionId = 15;
        $entityData = [
            'entity_id' => $entityId,
            'created_in' => $createdIn
        ];
        $arguments = [
            'row_id' => $rowId,
            'created_in' => $rollbackId,
            'updated_in' => $nextVersionId,
            'is_rollback' => true,
            'copy_origin_in' => $createdIn
        ];

        /** @var UpdateInterface|MockObject $versionMock */
        $versionMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMock();
        $versionMock->expects($this->once())->method('getId')->willReturn($versionId);

        $this->readEntityVersionMock
            ->expects($this->once())
            ->method('getRollbackVersionIds')
            ->with($this->entityType, $createdIn, $endVersionId, $entityId)
            ->willReturn([$rollbackId]);
        $this->versionManagerMock
            ->method('setCurrentVersionId')
            ->withConsecutive([$rollbackId], [$versionId]);
        $this->versionManagerMock->expects($this->once())->method('getVersion')->willReturn($versionMock);
        $this->readEntityVersionMock
            ->expects($this->once())
            ->method('getCurrentVersionRowId')
            ->with($this->entityType, $entityId)
            ->willReturn($rowId);
        $this->readEntityVersionMock
            ->expects($this->once())
            ->method('getNextVersionId')
            ->with($this->entityType, $rollbackId, $entityId)
            ->willReturn($nextVersionId);
        $metadata = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $hydrator = $this->getMockBuilder(HydratorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->with($this->entityType)
            ->willReturn($metadata);
        $this->hydratorPool->expects($this->once())
            ->method('getHydrator')
            ->with($this->entityType)
            ->willReturn($hydrator);
        $hydrator->expects($this->once())
            ->method('extract')
            ->with($this->entityMock)
            ->willReturn($entityData);
        $metadata->expects($this->once())
            ->method('getIdentifierField')
            ->willReturn('entity_id');
        $metadata->expects($this->once())
            ->method('getLinkField')
            ->willReturn('row_id');
        $this->updateEntityVersion->expects($this->once())
            ->method('execute')
            ->with($this->entityMock, $arguments)
            ->willReturn(true);
        $this->model->execute($this->entityMock, $endVersionId);
    }
}
