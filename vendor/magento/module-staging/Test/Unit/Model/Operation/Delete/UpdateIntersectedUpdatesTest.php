<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Operation\Delete;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\Db\DeleteRow as DeleteEntityRow;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Entity\Builder;
use Magento\Staging\Model\Entity\VersionLoader;
use Magento\Staging\Model\Operation\Delete\UpdateIntersectedRollbacks;
use Magento\Staging\Model\Operation\Delete\UpdateIntersectedUpdates;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateIntersectedUpdatesTest extends TestCase
{
    /**
     * @var TypeResolver|MockObject
     */
    private $typeResolverMock;

    /**
     * @var ReadEntityVersion|MockObject
     */
    private $readEntityVersionMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var DeleteEntityRow|MockObject
     */
    private $deleteEntityRowMock;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var UpdateIntersectedRollbacks|MockObject
     */
    private $intersectedRollbacksMock;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManagerMock;

    /**
     * @var Builder|MockObject
     */
    private $builderMock;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var AbstractModel|MockObject
     */
    private $entityMock;

    /**
     * @var UpdateInterface|MockObject
     */
    private $versionMock;

    /**
     * @var EntityMetadataInterface|MockObject
     */
    private $metadataMock;

    /**
     * @var HydratorInterface|MockObject
     */
    private $hydratorMock;

    /**
     * @var VersionLoader|MockObject
     */
    private $versionLoaderMock;

    /**
     * @var UpdateIntersectedUpdates
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->entityType = 'TestModule\Api\Data\TestModuleInterface';
        $this->entityMock = $this->getMockForAbstractClass(
            AbstractModel::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->typeResolverMock = $this->createMock(TypeResolver::class);
        $this->versionMock = $this->getMockForAbstractClass(
            UpdateInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->metadataMock = $this->getMockForAbstractClass(
            EntityMetadataInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->hydratorMock = $this->getMockForAbstractClass(
            HydratorInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->readEntityVersionMock = $this->createMock(ReadEntityVersion::class);
        $this->intersectedRollbacksMock = $this->createMock(UpdateIntersectedRollbacks::class);
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->deleteEntityRowMock = $this->createMock(DeleteEntityRow::class);
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->entityManagerMock = $this->createMock(EntityManager::class);
        $this->builderMock = $this->createMock(Builder::class);
        $this->typeResolverMock
            ->method('resolve')
            ->with($this->entityMock)
            ->willReturn($this->entityType);
        $this->versionManagerMock->method('getCurrentVersion')->willReturn($this->versionMock);
        $this->metadataPoolMock->method('getMetadata')->with($this->entityType)->willReturn(
            $this->metadataMock
        );
        $this->metadataPoolMock->expects($this->once())->method('getHydrator')->with($this->entityType)->willReturn(
            $this->hydratorMock
        );
        $this->versionLoaderMock = $this->createMock(VersionLoader::class);
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            UpdateIntersectedUpdates::class,
            [
                'typeResolver' => $this->typeResolverMock,
                'intersectedRollbacks' => $this->intersectedRollbacksMock,
                'versionManager' => $this->versionManagerMock,
                'readEntityVersion' => $this->readEntityVersionMock,
                'deleteEntityRow' => $this->deleteEntityRowMock,
                'metadataPool' => $this->metadataPoolMock,
                'entityManager' => $this->entityManagerMock,
                'builder' => $this->builderMock,
                'versionLoader' => $this->versionLoaderMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecuteTemporaryUpdate(): void
    {
        $rollbackId = 10;
        $entityId = 'entity_id';
        $entityData = ['created_in' => 7, $entityId => $entityId];
        $nextVersionId = 11;
        $nextVersion = ['updated_in' => 12, $entityId => 11];
        $previousVersionId = 1;
        $this->versionMock->expects($this->once())->method('getRollbackId')->willReturn($rollbackId);
        $this->hydratorMock->expects($this->once())
            ->method('extract')
            ->with($this->entityMock)
            ->willReturn($entityData);
        $this->metadataMock->method('getIdentifierField')->willReturn($entityId);
        $this->metadataMock->method('getLinkField')->willReturn($entityId);
        $this->readEntityVersionMock->expects($this->once())
            ->method('getNextVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($nextVersionId);
        $entityTable = 'table_name';
        $this->metadataMock->method('getEntityTable')->willReturn($entityTable);
        $adapterMock = $this->getMockForAbstractClass(
            AdapterInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['select']
        );
        $this->metadataMock->method('getEntityConnection')->willReturn($adapterMock);
        $selectMock = $this->createPartialMock(Select::class, ['from', 'where', 'setPart']);
        $selectMock->method('from')->with(['entity_table' => $entityTable])->willReturnSelf();
        $selectMock
            ->method('where')
            ->withConsecutive(
                ['created_in = ?', $nextVersionId],
                [$entityId . ' = ?', $this->entityMock[$entityId]]
            )
            ->willReturnOnConsecutiveCalls($selectMock, $selectMock);
        $selectMock->method('setPart')->with('disable_staging_preview', true)->willReturnSelf();
        $adapterMock->expects($this->once())->method('select')->willReturn($selectMock);
        $adapterMock->expects($this->once())->method('fetchRow')->with($selectMock)->willReturn($nextVersion);

        $adapterMock->expects($this->once())->method('update')->with(
            $entityTable,
            ['updated_in' => $nextVersion['updated_in']],
            [
                $entityId . ' = ?' => $nextVersion[$entityId],
                'created_in = ?' => $previousVersionId
            ]
        )
            ->willReturn([]);
        $this->readEntityVersionMock->expects($this->once())
            ->method('getPreviousVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($previousVersionId);
        $this->deleteEntityRowMock->expects($this->once())
            ->method('execute')->with($this->entityType, $nextVersion);

        $this->model->execute($this->entityMock);
    }

    /**
     * @return void
     */
    public function testExecutePermanentUpdate(): void
    {
        $entityId = 'entity_id';
        $entityTable = 'table_name';
        $entityData = ['created_in' => 7, $entityId => $entityId];
        $nextVersionId = 11;
        $nextVersion = ['created_in' => 12, $entityId => 11];
        $previousVersionId = 1;
        $previousPermanentId = 1;
        $nextPermanentId = 2;

        $this->hydratorMock->expects($this->once())
            ->method('extract')
            ->with($this->entityMock)
            ->willReturn($entityData);
        $this->metadataMock->method('getIdentifierField')->willReturn($entityId);
        $this->metadataMock->method('getEntityTable')->willReturn($entityTable);

        $this->readEntityVersionMock->expects($this->once())
            ->method('getNextVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($nextVersionId);
        $adapterMock = $this->getMockForAbstractClass(
            AdapterInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['select']
        );
        $this->metadataMock->method('getEntityConnection')->willReturn($adapterMock);
        $selectMock = $this->createPartialMock(Select::class, ['from', 'where', 'setPart']);
        $selectMock->method('from')->with(['entity_table' => $entityTable])->willReturnSelf();
        $selectMock
            ->method('where')
            ->withConsecutive(
                ['created_in = ?', $nextVersionId],
                [$entityId . ' = ?', $this->entityMock[$entityId]]
            )
            ->willReturnOnConsecutiveCalls($selectMock, $selectMock);
        $selectMock->method('setPart')->with('disable_staging_preview', true)->willReturnSelf();

        $adapterMock->expects($this->once())->method('select')->willReturn($selectMock);
        $adapterMock->expects($this->once())->method('fetchRow')->with($selectMock)->willReturn($nextVersion);

        $this->readEntityVersionMock->expects($this->once())
            ->method('getPreviousVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($previousPermanentId);
        $adapterMock->expects($this->once())->method('update')->with(
            $entityTable,
            ['updated_in' => $nextVersion['created_in']],
            [
                $entityId . ' = ?' => $nextVersion[$entityId],
                'created_in = ?' => $previousVersionId
            ]
        )
            ->willReturn([]);
        $this->readEntityVersionMock->expects($this->once())
            ->method('getPreviousPermanentVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($previousPermanentId);
        $this->readEntityVersionMock->expects($this->once())
            ->method('getNextPermanentVersionId')
            ->with($this->entityType, $entityData['created_in'], $entityData[$entityId])
            ->willReturn($nextPermanentId);
        $this->versionManagerMock->expects($this->never())->method('getVersion')->willReturn($this->versionMock);
        $this->versionMock->expects($this->never())->method('getId');
        $this->versionManagerMock->expects($this->never())->method('setCurrentVersionId');
        $prevPermanentEntity = '';
        $this->versionLoaderMock->expects($this->once())
            ->method('load')
            ->with($this->entityMock, $entityId, $previousVersionId)
            ->willReturn($prevPermanentEntity);
        $this->intersectedRollbacksMock->expects($this->once())
            ->method('execute')
            ->with($prevPermanentEntity, $nextPermanentId);

        $this->model->execute($this->entityMock);
    }
}
