<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Operation;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\Action\UpdateVersion;
use Magento\Staging\Model\Operation\Update as UpdateOperation;
use Magento\Staging\Model\Operation\Update\CampaignIntegrity;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Operation\Update\RescheduleUpdate;
use Magento\Staging\Model\Operation\Update\UpdateEntityVersion;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionInfo;
use Magento\Staging\Model\VersionInfoProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateTest extends TestCase
{
    /**
     * @var UpdateOperation|MockObject
     */
    private $updateOperation;

    /**
     * @var ReadEntityVersion|MockObject
     */
    private $entityVersion;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPool;

    /**
     * @var UpdateVersion|MockObject
     */
    private $updateVersion;

    /**
     * @var CreateEntityVersion|MockObject
     */
    private $createEntityVersion;

    /**
     * @var UpdateEntityVersion|MockObject
     */
    private $updateEntityVersion;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnection;

    /**
     * @var EventManager|MockObject
     */
    private $eventManager;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepository;

    /**
     * @var RescheduleUpdate|MockObject
     */
    private $rescheduleUpdate;

    /**
     * @var CampaignIntegrity|MockObject
     */
    private $campaignIntegrity;

    /**
     * @var TypeResolver|MockObject
     */
    private $typeResolver;

    /**
     * @var  AdapterInterface|MockObject
     */
    private $adapter;

    /**
     * @var stdClass
     */
    private $entity;

    /**
     * @var EntityMetadataInterface|MockObject
     */
    private $metadata;

    /**
     * @var HydratorInterface|MockObject
     */
    private $hydrator;

    /**
     * @var UpdateInterface|MockObject
     */
    private $update;

    /**
     * @var VersionInfoProvider|MockObject
     */
    private $versionInfoProvider;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->typeResolver = $this->getMockBuilder(TypeResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateVersion = $this->getMockBuilder(UpdateVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManager = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->campaignIntegrity = $this->getMockBuilder(CampaignIntegrity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rescheduleUpdate = $this->getMockBuilder(RescheduleUpdate::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepository = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateEntityVersion = $this->getMockBuilder(UpdateEntityVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->createEntityVersion = $this->getMockBuilder(CreateEntityVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityVersion = $this->getMockBuilder(ReadEntityVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadata = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMock();
        $this->adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->hydrator = $this->getMockBuilder(HydratorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->entity = new stdClass();
        $this->update = $this->getMockBuilder(UpdateInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->versionInfoProvider = $this->getMockBuilder(VersionInfoProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->updateOperation = $objectManagerHelper->getObject(
            UpdateOperation::class,
            [
                'typeResolver' => $this->typeResolver,
                'entityVersion' => $this->entityVersion,
                'metadataPool' => $this->metadataPool,
                'updateVersion' => $this->updateVersion,
                'createEntityVersion' => $this->createEntityVersion,
                'updateEntityVersion' => $this->updateEntityVersion,
                'resourceConnection' => $this->resourceConnection,
                'eventManager' => $this->eventManager,
                'updateRepository' => $this->updateRepository,
                'rescheduleUpdate' => $this->rescheduleUpdate,
                'campaignIntegrity' => $this->campaignIntegrity,
                'versionInfoProvider' => $this->versionInfoProvider
            ]
        );
    }

    /**
     * @dataProvider updateDataProvider
     * @param array $arguments
     * @param array $entityData
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute($arguments = [], $entityData = []): void
    {
        $rowId = $entityData['row_id'] ?? null;
        $rollBackId = $entityData['rollback_id'] ?? null;
        $identifier = $entityData['id'] ?? null;
        $entityType = PageInterface::class;

        $versionInfo = $this->getMockBuilder(VersionInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $versionInfo->expects($this->any())
            ->method('getRowId')
            ->willReturn($rowId);
        $versionInfo->expects($this->any())
            ->method('getIdentifier')
            ->willReturn($identifier);
        $versionInfo->expects($this->any())
            ->method('getCreatedIn')
            ->willReturn($arguments['created_in'] ?? null);
        $versionInfo->expects($this->any())
            ->method('getUpdatedIn')
            ->willReturn($arguments['updated_in'] ?? null);
        $this->versionInfoProvider->expects($this->once())
            ->method('getVersionInfo')
            ->with($this->entity)
            ->willReturn($versionInfo);
        $this->typeResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($this->entity)
            ->willReturn($entityType);
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->metadata);

        $connectionName = 'Connection';

        $this->metadata->expects($this->once())
            ->method('getEntityConnectionName')
            ->willReturn($connectionName);
        $this->metadata->expects($this->once())
            ->method('getIdentifierField')
            ->willReturn('id');
        $this->resourceConnection->expects($this->once())
            ->method('getConnectionByName')
            ->with($connectionName)
            ->willReturn($this->adapter);
        $this->adapter->expects($this->once())
            ->method('beginTransaction')
            ->willReturnSelf();
        $this->adapter->expects($this->never())
            ->method('rollBack')
            ->willReturnSelf();
        $this->metadataPool->expects($this->once())
            ->method('getHydrator')
            ->with($entityType)
            ->willReturn($this->hydrator);
        $this->hydrator
            ->method('extract')
            ->with($this->entity)
            ->willReturn($entityData);
        $this->updateRepository->expects($this->any())
            ->method('get')
            ->with($arguments['created_in'])
            ->willReturn($this->update);
        $this->update->expects($this->any())
            ->method('getRollbackId')
            ->willReturn($rollBackId);
        $this->eventManager
            ->method('dispatch')
            ->withConsecutive(
                [
                    'entity_save_before',
                    ['entity_type' => $entityType, 'entity' => $this->entity]
                ],
                [
                    'entity_manager_save_after',
                    ['entity_type' => $entityType, 'entity' => $this->entity]
                ]
            );
        $this->eventManager
            ->method('dispatchEntityEvent')
            ->withConsecutive(
                [
                    $entityType,
                    'save_before',
                    ['entity' => $this->entity]
                ],
                [
                    $entityType,
                    'save_after',
                    ['entity' => $this->entity]
                ]
            );

        $changedArguments = $arguments;

        if ($rollBackId && $rowId === null) {
            $changedArguments['updated_in'] = $rollBackId;
        } else {
            if (!$rowId) {
                $updateId = 999999999;
                $changedArguments['updated_in'] = 146469999;
                $this->update->expects($this->once())
                    ->method('getId')
                    ->willReturn($updateId);
                $this->entityVersion->expects($this->once())
                    ->method('getNextVersionId')
                    ->with($entityType, $updateId, $identifier)
                    ->willReturn($changedArguments['updated_in']);
            }
        }

        if ($rowId) {
            $this->metadata->expects($this->once())
                ->method('getLinkField')
                ->willReturn('row_id');

            if ($rollBackId) {
                $this->campaignIntegrity->expects($this->once())
                    ->method('createRollbackPoint')
                    ->with($this->update, $this->entity);
            } else {
                $this->updateEntityVersion->expects($this->once())
                    ->method('execute')
                    ->with($this->entity, array_merge($changedArguments, ['row_id' => $rowId]))
                    ->willReturn($this->entity);
            }
        } else {
            $this->updateVersion->expects($this->once())
                ->method('execute')
                ->with($entityType, $identifier);
            $this->createEntityVersion->expects($this->once())
                ->method('execute')
                ->with($this->entity, $changedArguments);
            $this->campaignIntegrity->expects($this->once())
                ->method('createRollbackPoint')
                ->with($this->update, $this->entity);
        }

        $this->campaignIntegrity->expects($this->once())
            ->method('synchronizeAffectedCampaigns')
            ->with($this->update, $this->entity);
        $this->adapter->expects($this->once())
            ->method('commit')
            ->willReturnSelf();

        $this->updateOperation->execute($this->entity, $arguments);
    }

    /**
     * Dataprovider fot update.
     *
     * @return array
     */
    public function updateDataProvider(): array
    {
        return [
            'testExecuteWithCreadtedInUpdatedInRefreshing' => [
                'arguments' => [
                    'created_in' => '1464685260',
                    'updated_in' => '1464685261'
                ],
                'entityData' => [
                    'id' => 1,
                    'row_id' => 2
                ],
            ],
            'testExecuteWithoutCurrentRowIdAndWithRollbackId' => [
                'arguments' => [
                    'created_in' => '1464685260',
                    'updated_in' => '1464685261'
                ],
                'entityData' => [
                    'id' => 1,
                    'rollback_id' => '146469999'
                ],
            ],
            'testExecuteWithoutCurrentRowIdAndWithoutRollbackId' => [
                'arguments' => [
                    'created_in' => '1464685260',
                    'updated_in' => '1464685261'
                ],
                'entityData' => [
                    'id' => 1
                ],
            ],
            'testExecuteForPermanentUpdateMadeTemporary' => [
                'arguments' => [
                    'created_in' => '1464685260',
                    'updated_in' => '1464685261'
                ],
                'entityData' => [
                    'id' => 1,
                    'row_id' => 2,
                    'rollback_id' => '146469999'
                ],
            ],
        ];
    }
}
