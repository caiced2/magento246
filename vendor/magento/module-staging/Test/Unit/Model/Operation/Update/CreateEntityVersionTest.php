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
use Magento\Framework\EntityManager\Operation\Create\CreateAttributes;
use Magento\Framework\EntityManager\Operation\Create\CreateExtensions;
use Magento\Framework\EntityManager\Operation\Create\CreateMain;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateEntityVersionTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $metadataPoolMock;

    /**
     * @var MockObject
     */
    private $createMainMock;

    /**
     * @var MockObject
     */
    private $createExtensionMock;

    /**
     * @var MockObject
     */
    private $createRelationMock;

    /**
     * @var CreateEntityVersion
     */
    private $model;

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
     * @var string
     */
    private $entityType;

    protected function setUp(): void
    {
        $this->entityType = 'EntityType';
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->metaDataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $this->hydratorMock = $this->getMockForAbstractClass(HydratorInterface::class);
        $this->objectMock = $this->createMock(AbstractExtensibleModel::class);
        $this->createMainMock = $this->createMock(CreateMain::class);
        $this->createRelationMock = $this->createMock(
            CreateExtensions::class
        );
        $this->createExtensionMock = $this->createMock(
            CreateAttributes::class
        );
        $typeResolverMock = $this->createMock(TypeResolver::class);
        $typeResolverMock->expects($this->any())
            ->method('resolve')
            ->willReturn($this->entityType);
        $this->model = new CreateEntityVersion(
            $typeResolverMock,
            $this->metadataPoolMock,
            $this->createMainMock,
            $this->createRelationMock,
            $this->createExtensionMock
        );
    }

    public function testExecute()
    {
        $createdIn = 1;
        $updatedIn = 2;
        $entityData = [
            'linkedField' => 1
        ];
        $arguments = [
            'created_in' => $createdIn,
            'updated_in' => $updatedIn
        ];
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
        $this->metaDataMock->expects($this->once())->method('getLinkField')->willReturn('linkedField');
        $this->hydratorMock
            ->expects($this->once())
            ->method('hydrate')
            ->with($this->objectMock, ['linkedField' => null])
            ->willReturn($this->objectMock);
        $this->createMainMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->objectMock, $arguments);
        $this->createExtensionMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->objectMock, $arguments);
        $this->createRelationMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->objectMock, $arguments);
        $arguments = [
            'created_in' => $createdIn,
            'updated_in' => $updatedIn
        ];
        $this->model->execute($this->objectMock, $arguments);
    }
}
