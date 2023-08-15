<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Operation\Update;

use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Staging\Model\Entity\VersionLoader;
use Magento\Staging\Model\Operation\Delete\UpdateIntersectedRollbacks;
use Magento\Staging\Model\Operation\Update\DefaultPermanentUpdateProcessor;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultPermanentUpdateProcessorTest extends TestCase
{
    /**
     * @var DefaultPermanentUpdateProcessor
     */
    private $model;

    /**
     * @var ReadEntityVersion|MockObject
     */
    private $entityVersionMock;

    /**
     * @var UpdateIntersectedRollbacks|MockObject
     */
    private $updateIntersectedUpdatesMock;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var MockObject
     */
    private $objectMock;

    /**
     * @var MockObject
     */
    private $hydratorMock;

    /**
     * @var MockObject
     */
    private $metaDataMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var VersionLoader|MockObject
     */
    private $versionLoaderMock;

    /**
     * @var string
     */
    private $entityType;

    protected function setUp(): void
    {
        $this->entityType = 'EntityType';
        $this->metaDataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $this->hydratorMock = $this->getMockForAbstractClass(HydratorInterface::class);
        $this->objectMock = $this->createMock(AbstractExtensibleModel::class);
        $this->entityVersionMock = $this->createMock(ReadEntityVersion::class);
        $this->updateIntersectedUpdatesMock = $this->createMock(UpdateIntersectedRollbacks::class);
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        /** @var MockObject|TypeResolver $typeResolverMock */
        $typeResolverMock = $this->createMock(TypeResolver::class);
        $typeResolverMock->expects($this->any())
            ->method('resolve')
            ->willReturn($this->entityType);
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->versionLoaderMock = $this->createMock(VersionLoader::class);
        $this->model = new DefaultPermanentUpdateProcessor(
            $typeResolverMock,
            $this->entityVersionMock,
            $this->updateIntersectedUpdatesMock,
            $this->metadataPoolMock,
            $this->versionManagerMock,
            $this->versionLoaderMock
        );
    }

    public function testProcess()
    {
        $versionId = 1;
        $rollbackId = null;
        $entityData = [
            'id' => 1
        ];
        $nextVersionId = 2;
        $nextPermanentVersionId = 3;
        $this->metadataPoolMock
            ->expects($this->once())
            ->method('getHydrator')
            ->with($this->entityType)
            ->willReturn($this->hydratorMock);
        $this->metadataPoolMock
            ->expects($this->once())
            ->method('getMetadata')
            ->with($this->entityType)
            ->willReturn($this->metaDataMock);
        $this->hydratorMock
            ->expects($this->once())
            ->method('extract')
            ->with($this->objectMock)
            ->willReturn($entityData);
        $this->metaDataMock->expects($this->once())->method('getIdentifierField')->willReturn('id');
        $this->entityVersionMock
            ->expects($this->once())
            ->method('getNextVersionId')
            ->with($this->entityType, $versionId, 1)
            ->willReturn($nextVersionId);
        $this->entityVersionMock
            ->expects($this->once())
            ->method('getNextPermanentVersionId')
            ->with($this->entityType, $versionId, 1)
            ->willReturn($nextPermanentVersionId);
        $this->updateIntersectedUpdatesMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->objectMock, $nextPermanentVersionId);
        $this->model->process($this->objectMock, $versionId, $rollbackId);
    }

    public function testProcessPermanentUpdate()
    {
        $versionId = 1;
        $rollbackId = null;
        $entityData = [
            'id' => 1
        ];
        $nextVersionId = 2;
        $nextPermanentVersionId = 2;
        $this->metadataPoolMock
            ->expects($this->once())
            ->method('getHydrator')
            ->with($this->entityType)
            ->willReturn($this->hydratorMock);
        $this->metadataPoolMock
            ->expects($this->once())
            ->method('getMetadata')
            ->with($this->entityType)
            ->willReturn($this->metaDataMock);
        $this->hydratorMock
            ->expects($this->once())
            ->method('extract')
            ->with($this->objectMock)
            ->willReturn($entityData);
        $this->metaDataMock->expects($this->once())->method('getIdentifierField')->willReturn('id');
        $this->entityVersionMock
            ->expects($this->once())
            ->method('getNextVersionId')
            ->with($this->entityType, $versionId, 1)
            ->willReturn($nextVersionId);
        $this->entityVersionMock
            ->expects($this->once())
            ->method('getNextPermanentVersionId')
            ->with($this->entityType, $versionId, 1)
            ->willReturn($nextPermanentVersionId);
        $this->updateIntersectedUpdatesMock
            ->expects($this->never())
            ->method('execute');
        $this->model->process($this->objectMock, $versionId, $rollbackId);
    }

    /**
     * @return void
     */
    public function testProcessPermanentMadeTemporaryUpdate()
    {
        $firstVersionId = 1;
        $versionId = 2;
        $rollbackId = 5;
        $entityData = ['id' => 1];
        $nextVersionId = 3;
        $nextPermanentVersionId = 4;

        $this->metadataPoolMock->expects($this->once())
            ->method('getHydrator')
            ->with($this->entityType)
            ->willReturn($this->hydratorMock);
        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with($this->entityType)
            ->willReturn($this->metaDataMock);
        $this->hydratorMock->expects($this->once())
            ->method('extract')
            ->with($this->objectMock)
            ->willReturn($entityData);
        $this->metaDataMock->expects($this->once())->method('getIdentifierField')->willReturn('id');
        $this->entityVersionMock->expects($this->once())
            ->method('getNextVersionId')
            ->with($this->entityType, $versionId, $entityData['id'])
            ->willReturn($nextVersionId);
        $this->entityVersionMock->expects($this->once())
            ->method('getNextPermanentVersionId')
            ->with($this->entityType, $versionId, $entityData['id'])
            ->willReturn($nextPermanentVersionId);
        $this->updateIntersectedUpdatesMock->expects($this->once())
            ->method('execute')
            ->with($this->objectMock, $nextPermanentVersionId);
        $this->versionManagerMock->expects($this->once())
            ->method('setCurrentVersionId')
            ->withConsecutive([$versionId]);
        $this->entityVersionMock->expects($this->once())
            ->method('getPreviousPermanentVersionId')
            ->with($this->entityType, $versionId, $entityData['id'])
            ->willReturn($firstVersionId);
        $this->versionLoaderMock->expects($this->once())
            ->method('load')
            ->with($this->objectMock, $entityData['id'], $firstVersionId)
            ->willReturn($this->objectMock);

        $this->model->process($this->objectMock, $versionId, $rollbackId);
    }
}
