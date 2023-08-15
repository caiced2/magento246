<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Operation\Update;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Operation\Update\DefaultTemporaryUpdateProcessor;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager\Proxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DefaultTemporaryUpdateProcessorTest extends TestCase
{
    /**
     * @var DefaultTemporaryUpdateProcessor
     */
    private $model;

    /**
     * @var MockObject
     */
    private $createEntityVersionMock;

    /**
     * @var MockObject
     */
    private $entityVersionMock;

    /**
     * @var MockObject
     */
    private $entityManagerMock;

    /**
     * @var MockObject
     */
    private $versionManagerMock;

    /**
     * @var MockObject
     */
    private $metadataPoolMock;

    /**
     * @var MockObject
     */
    private $typeResolverMock;

    /**
     * @var ObjectManager|MockObject
     */
    private $objectManager;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->createEntityVersionMock = $this->createMock(CreateEntityVersion::class);
        $this->entityVersionMock = $this->createMock(ReadEntityVersion::class);
        $this->entityManagerMock = $this->createMock(EntityManager::class);
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->typeResolverMock = $this->createMock(TypeResolver::class);
        $this->versionManagerMock = $this->getMockBuilder(Proxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCurrentVersionId'])
            ->getMock();

        $this->model = new DefaultTemporaryUpdateProcessor(
            $this->typeResolverMock,
            $this->createEntityVersionMock,
            $this->entityVersionMock,
            $this->versionManagerMock,
            $this->entityManagerMock,
            $this->metadataPoolMock
        );

        $this->objectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $reflection = new ReflectionClass(DefaultTemporaryUpdateProcessor::class);
        $reflectionProperty = $reflection->getProperty('objectManager');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->model, $this->objectManager);
    }

    /**
     * @return void
     */
    public function testProcess(): void
    {
        $entityType = UpdateInterface::class;
        $entityMock = $this->getMockForAbstractClass($entityType, [], '', false, false);
        $metadataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class, [], '', false, false);
        $hydratorMock = $this->getMockForAbstractClass(HydratorInterface::class, [], '', false, false);
        $identifierField = 'id';
        $entityId = 1;
        $entityData = [$identifierField => $entityId];
        $prevVersion = 1000;
        $versionId = 2000;
        $nextVersion = 3000;
        $rollbackId = null;

        $this->typeResolverMock->expects($this->any())
            ->method('resolve')
            ->willReturn($entityType);
        $this->metadataPoolMock->expects($this->once())->method('getHydrator')->with($entityType)
            ->willReturn($hydratorMock);
        $this->metadataPoolMock->expects($this->once())->method('getMetadata')->with($entityType)
            ->willReturn($metadataMock);
        $hydratorMock->expects($this->once())->method('extract')->with($entityMock)->willReturn($entityData);
        $metadataMock->expects($this->once())->method('getIdentifierField')->willReturn($identifierField);
        $this->entityVersionMock->expects($this->once())->method('getPreviousVersionId')->willReturn($prevVersion);
        $this->entityVersionMock->expects($this->once())->method('getNextVersionId')->willReturn($nextVersion);
        $this->versionManagerMock->expects($this->atLeastOnce())->method('setCurrentVersionId')->withConsecutive(
            [$prevVersion],
            [$rollbackId],
            [$versionId]
        );

        $previousEntity = $this->getMockForAbstractClass($entityType);
        $this->objectManager->expects(static::once())
            ->method('create')
            ->with($entityType)
            ->willReturn($previousEntity);

        $entityMock->expects(static::once())
            ->method('getId')
            ->willReturn($entityId);

        $this->entityManagerMock->expects(static::once())
            ->method('load')
            ->with($previousEntity, $entityId);
        $this->createEntityVersionMock->expects($this->once())->method('execute');

        $this->assertSame($entityMock, $this->model->process($entityMock, $versionId, $rollbackId));
    }
}
