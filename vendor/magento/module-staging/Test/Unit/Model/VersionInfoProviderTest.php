<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Staging\Model\VersionInfo;
use Magento\Staging\Model\VersionInfoFactory;
use Magento\Staging\Model\VersionInfoProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VersionInfoProviderTest extends TestCase
{
    /**
     * @var VersionInfo|MockObject
     */
    private $versionInfoMock;

    /**
     * @var Select|MockObject
     */
    private $selectMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var HydratorInterface|MockObject
     */
    private $entityHydratorMock;

    /**
     * @var HydratorPool|MockObject
     */
    private $hydratorPoolMock;

    /**
     * @var TypeResolver|MockObject
     */
    private $typeResolverMock;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var MockObject
     */
    private $versionInfoFactoryMock;

    /**
     * @var VersionHistoryInterface|MockObject
     */
    private $versionHistoryMock;

    /**
     * @var EntityMetadataInterface|MockObject
     */
    private $entityMetadataMock;

    /**
     * @var VersionInfoProvider
     */
    private $model;

    protected function setUp(): void
    {
        $this->entityMetadataMock = $this->getMockBuilder(
            EntityMetadataInterface::class
        )->getMockForAbstractClass();
        $this->entityHydratorMock = $this->getMockBuilder(HydratorInterface::class)
            ->getMockForAbstractClass();
        $this->hydratorPoolMock = $this->getMockBuilder(HydratorPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->typeResolverMock = $this->getMockBuilder(TypeResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(
            AdapterInterface::class
        )->getMockForAbstractClass();
        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionInfoFactoryMock = $this->getMockBuilder(VersionInfoFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->versionInfoMock = $this->getMockBuilder(VersionInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionHistoryMock = $this->getMockBuilder(VersionHistoryInterface::class)
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            VersionInfoProvider::class,
            [
                'typeResolver' => $this->typeResolverMock,
                'metadataPool' => $this->metadataPoolMock,
                'hydratorPool' => $this->hydratorPoolMock,
                'resourceConnection' => $this->resourceConnectionMock,
                'versionHistory' => $this->versionHistoryMock,
                'versionInfoFactory' => $this->versionInfoFactoryMock,
            ]
        );
    }

    public function testGetVersionInfoThrowInvalidEntity()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Invalid entity');
        $this->hydratorPoolMock->expects($this->once())->method('getHydrator')->willReturn($this->entityHydratorMock);
        $this->metadataPoolMock->expects($this->once())->method('getMetadata')->willReturn($this->entityMetadataMock);
        $this->model->getVersionInfo(new \stdClass());
    }

    /**
     * @dataProvider getVersionDataProvider
     */
    public function testGetVersionInfoForActiveVersion($version, $currentVersion)
    {
        $this->versionHistoryMock->expects($currentVersion ? $this->once() : $this->never())
            ->method('getCurrentId')
            ->willReturn($currentVersion);
        $idField = 'id';
        $linkField = 'link_id';
        $data = [
            $idField => 42
        ];
        $expectedData = [$linkField => 50, $idField => $data[$idField],'created_in' => 42, 'updated_in' => 99991212];
        $entity = new \stdClass();
        $this->hydratorPoolMock->expects($this->once())->method('getHydrator')->willReturn($this->entityHydratorMock);
        $this->metadataPoolMock->expects($this->once())->method('getMetadata')->willReturn($this->entityMetadataMock);
        $this->entityMetadataMock->expects($this->atLeastOnce())->method('getIdentifierField')->willReturn($idField);
        $this->entityMetadataMock->expects($this->atLeastOnce())->method('getLinkField')->willReturn($linkField);
        $this->entityHydratorMock->expects($this->once())->method('extract')->with($entity)->willReturn($data);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getConnectionByName')
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())->method('select')->willReturn($this->selectMock);
        $this->selectMock->expects($this->once())->method('from')->willReturnSelf();
        if ($currentVersion != $version) {
            $this->selectMock->expects($this->exactly(2))->method('where')->willReturnSelf();
            $this->selectMock->expects($this->once())->method('setPart')
                ->with('disable_staging_preview', true)
                ->willReturnSelf();
        } else {
            $this->selectMock->expects($this->exactly(1))->method('where')->willReturnSelf();
        }
        $this->connectionMock->expects($this->once())->method('fetchRow')
            ->with($this->selectMock)
            ->willReturn(
                $expectedData
            );
        $this->versionInfoFactoryMock->expects($this->once())->method('create')
            ->with(
                [
                    'rowId' => $expectedData[$linkField],
                    'identifier' => $expectedData[$idField],
                    'createdIn' => $expectedData['created_in'],
                    'updatedIn' => $expectedData['updated_in']
                ]
            )
            ->willReturn($this->versionInfoMock);
        $this->assertSame($this->versionInfoMock, $this->model->getVersionInfo($entity, $version));
    }

    /**
     * @return array
     */
    public function getVersionDataProvider()
    {
        return [
            'null' => [null, null],
            'current' => [45, 45],
            '11 version' => [11, 1]
        ];
    }
}
