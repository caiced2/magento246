<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config;
use Magento\Framework\Data\AbstractDataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Model\Entity\RepositoryFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\Staging\Model\Entity\RetrieverPool;
use Magento\Staging\Model\ResourceModel\Db\DeleteObsoleteEntities;
use Magento\Staging\Model\ResourceModel\Db\GetNotIndexedEntities;
use Magento\Staging\Model\StagingApplier;
use Magento\Staging\Model\StagingApplierInterface;
use Magento\Staging\Model\StagingList;
use Magento\Staging\Model\VersionHistoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StagingApplierTest extends TestCase
{
    /**
     * @var StagingApplier
     */
    private $model;

    /**
     * @var CacheInterface|MockObject
     */
    private $cacheManagerMock;

    /**
     * @var Config|MockObject
     */
    private $scopeConfigCacheMock;

    /**
     * @var RepositoryFactory|MockObject
     */
    private $repositoryFactoryMock;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var ManagerInterface|MockObject
     */
    private $eventManagerMock;

    /**
     * @var VersionHistoryInterface|MockObject
     */
    private $versionHistoryMock;

    /**
     * @var StagingList|MockObject
     */
    private $stagingListMock;

    /**
     * @var CacheContext|MockObject
     */
    private $cacheContextMock;

    /**
     * @var RetrieverPool|MockObject
     */
    private $retrieverPoolMock;

    /**
     * @var DeleteObsoleteEntities|MockObject
     */
    private $deleteObsoleteEntitiesMock;

    /**
     * @var GetNotIndexedEntities|MockObject
     */
    private $getNotIndexedEntitiesMock;

    /**
     * @var StagingApplierInterface[]|MockObject
     */
    private $appliersMock;

    protected function setUp(): void
    {
        $this->cacheManagerMock = $this->getMockForAbstractClass(
            CacheInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->appliersMock = $this->getMockForAbstractClass(
            StagingApplierInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->scopeConfigCacheMock = $this->createMock(Config::class);
        $this->repositoryFactoryMock = $this->createMock(RepositoryFactory::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(
            UpdateRepositoryInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->eventManagerMock = $this->getMockForAbstractClass(
            ManagerInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->versionHistoryMock = $this->getMockForAbstractClass(
            VersionHistoryInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->stagingListMock = $this->createMock(StagingList::class);
        $this->cacheContextMock = $this->createMock(CacheContext::class);
        $this->retrieverPoolMock = $this->createMock(RetrieverPool::class);
        $this->deleteObsoleteEntitiesMock = $this->createMock(DeleteObsoleteEntities::class);
        $this->getNotIndexedEntitiesMock = $this->createMock(GetNotIndexedEntities::class);
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            StagingApplier::class,
            [
                'updateRepository' => $this->updateRepositoryMock,
                'scopeConfigCache' => $this->scopeConfigCacheMock,
                'cacheContext' => $this->cacheContextMock,
                'eventManager' => $this->eventManagerMock,
                'versionHistory' => $this->versionHistoryMock,
                'stagingList' => $this->stagingListMock,
                'deleteObsoleteEntities' => $this->deleteObsoleteEntitiesMock,
                'getNotIndexedEntities' => $this->getNotIndexedEntitiesMock,
                'cacheManager' => $this->cacheManagerMock,
                'appliers' => ['category' => $this->appliersMock],
                'retrieverPool' => $this->retrieverPoolMock,
                'repositoryFactory' => $this->repositoryFactoryMock

            ]
        );
    }

    /**
     * @dataProvider cacheTagsDataProvider
     */
    public function testExecute(array $tags, int $cleanCacheRequests)
    {
        $now = strtotime('now');
        $currentVersionId = '232232332';
        $oldVersionId = '1111111111';
        $entityType = ['category'];
        $entityIds = ['1'];
        $maximumInDB = 10;
        $this->updateRepositoryMock->expects($this->once())
            ->method('getVersionMaxIdByTime')
            ->with($now)
            ->willReturn($currentVersionId);
        $this->versionHistoryMock->expects($this->any())->method('getCurrentId')->willReturn($oldVersionId);
        $this->versionHistoryMock->expects($this->once())->method('setCurrentId');
        $this->scopeConfigCacheMock->expects($this->once())->method('clean');
        $this->stagingListMock->expects($this->any())->method('getEntityTypes')->willReturn($entityType);
        $this->getNotIndexedEntitiesMock->expects($this->once())
            ->method('execute')
            ->with($entityType[0], $oldVersionId, $currentVersionId)
            ->willReturn($entityIds);
        $this->appliersMock->expects($this->once())->method('execute')->with($entityIds);
        $object = $this->getMockBuilder(AbstractDataObject::class)
            ->setMethods(['save'])
            ->getMockForAbstractClass();
        $this->repositoryFactoryMock->expects($this->once())
            ->method('create')
            ->with($entityType[0])
            ->willReturn($object);
        $object->expects($this->once())->method('save');
        $retrieverInterfaceMock = $this->getMockForAbstractClass(
            RetrieverInterface::class,
            [],
            '',
            false,
            false,
            true
        );
        $this->retrieverPoolMock->expects($this->once())
            ->method('getRetriever')
            ->with($entityType[0])
            ->willReturn($retrieverInterfaceMock);
        $retrieverInterfaceMock->expects($this->once())->method('getEntity')->with($entityIds[0]);

        $this->cacheContextMock->expects($this->once())->method('getIdentities')->willReturn($tags);
        $this->cacheManagerMock->expects($this->exactly($cleanCacheRequests))->method('clean');
        $this->eventManagerMock->expects($this->exactly($cleanCacheRequests))
            ->method('dispatch')
            ->with('clean_cache_by_tags', ['object' => $this->cacheContextMock]);
        $this->versionHistoryMock->expects($this->once())->method('getMaximumInDB')->willReturn($maximumInDB);
        $this->deleteObsoleteEntitiesMock->expects($this->once())
            ->method('execute')
            ->with($entityType[0], $currentVersionId, $maximumInDB);
        $this->model->execute();
    }

    /**
     * @return array
     */
    public function cacheTagsDataProvider(): array
    {
        return [
            'withoutTags'  => [[], 0],
            'withTags' => [['tag1', 'tag2'], 1],
        ];
    }
}
