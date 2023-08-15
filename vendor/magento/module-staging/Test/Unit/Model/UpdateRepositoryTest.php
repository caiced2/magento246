<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Staging\Api\Data\UpdateSearchResultInterfaceFactory;
use Magento\Staging\Model\Entity\PeriodSync\Scheduler as PeriodSyncScheduler;
use Magento\Staging\Model\ResourceModel\Update\Collection;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\Update\Validator;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\UpdateRepository;
use Magento\Staging\Model\VersionHistoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\Staging\Model\UpdateRepository class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateRepositoryTest extends TestCase
{
    /**
     * @var UpdateRepository
     */
    protected $model;

    /**
     * @var MockObject|Update
     */
    protected $entityMock;

    /**
     * @var MockObject|\Magento\Staging\Model\ResourceModel\Update
     */
    protected $resourceMock;

    /**
     * @var MockObject
     */
    protected $updateMock;

    /**
     * @var MockObject
     */
    protected $updateFactoryMock;

    /**
     * @var MockObject|Validator
     */
    protected $validatorMock;

    /**
     * @var MockObject searchResultsFactoryMock
     */
    protected $searchResultsFactoryMock;

    /**
     * @var MockObject
     */
    private $versionHistoryMock;

    /**
     * @var MockObject
     */
    private $collectionProcessor;

    /**
     * @var PeriodSyncScheduler|MockObject
     */
    private $periodSyncSchedulerMock;

    protected function setUp(): void
    {
        $this->entityMock = $this->createMock(Update::class);
        $this->resourceMock = $this->createMock(\Magento\Staging\Model\ResourceModel\Update::class);
        $this->validatorMock = $this->createMock(Validator::class);
        $this->updateMock = $this->createMock(Update::class);
        $this->updateFactoryMock = $this->createPartialMock(UpdateFactory::class, ['create']);
        $this->versionHistoryMock = $this->getMockForAbstractClass(VersionHistoryInterface::class);
        $this->searchResultsFactoryMock = $this->getMockBuilder(UpdateSearchResultInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionProcessor = $this->createMock(
            CollectionProcessorInterface::class
        );
        $this->periodSyncSchedulerMock = $this->createMock(PeriodSyncScheduler::class);

        $this->model = new UpdateRepository(
            $this->searchResultsFactoryMock,
            $this->resourceMock,
            $this->updateFactoryMock,
            $this->validatorMock,
            $this->versionHistoryMock,
            $this->collectionProcessor,
            $this->periodSyncSchedulerMock
        );
    }

    /**
     * test save new permanent update
     */
    public function testSaveNewPermanent()
    {
        $this->validatorMock->expects($this->once())->method('validateCreate')->with($this->entityMock);
        $this->entityMock->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $startTime = date('m/d/y', time());
        //getIdForEntity
        $this->entityMock->expects($this->any())->method('getStartTime')->willReturn($startTime);
        $this->updateFactoryMock->expects($this->any())->method('create')->willReturn($this->updateMock);
        $this->resourceMock->expects($this->any())->method('load')->with($this->updateMock, strtotime($startTime));
        $this->updateMock->expects($this->any())->method('getId')->willReturn(null);

        $this->entityMock->expects($this->once())->method('setId')->with(strtotime($startTime));
        $this->entityMock->expects($this->once())->method('isObjectNew')->with(true);
        $this->entityMock->expects($this->once())->method('getEndTime')->willReturn(null);
        $this->entityMock->expects($this->once())->method('getRollbackId')->willReturn(null);
        $this->resourceMock->expects($this->once())->method('save')->with($this->entityMock);

        $this->assertEquals($this->entityMock, $this->model->save($this->entityMock));
    }

    public function testSaveStartTimeActiveCampaign()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $startTime = date('m/d/y', time());
        $this->validatorMock->expects($this->once())->method('validateUpdate')->with($this->entityMock);
        $this->entityMock->expects($this->any())->method('getId')->willReturn(strtotime($startTime));
        //getIdForEntity
        $this->entityMock->expects($this->any())->method('getStartTime')->willReturn($startTime);
        $this->updateFactoryMock->expects($this->any())->method('create')->willReturn($this->updateMock);
        $this->resourceMock->expects($this->any())->method('load')->with($this->updateMock, strtotime($startTime));
        $this->updateMock->expects($this->any())->method('getId')->willReturn(strtotime($startTime) - 1);

        $this->versionHistoryMock->expects($this->any())
            ->method('getCurrentId')
            ->willReturn(strtotime($startTime) + 1);

        $this->assertNull($this->model->save($this->entityMock));

        $this->expectExceptionMessage(
            "The start time can't be changed while the update is active. "
            . "Please wait until the update is complete and try again."
        );
    }

    public function testGetList()
    {
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->once())
            ->method('setSearchCriteria')
            ->with($searchCriteriaMock);
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteriaMock, $collectionMock);
        $this->searchResultsFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $list = $this->model->getList($searchCriteriaMock);
        $this->assertSame($collectionMock, $list);
    }

    /**
     * test edit existed permanent update with changed Start Time
     */
    public function testSaveExistedPermanent()
    {
        $this->validatorMock->expects($this->once())->method('validateupdate')->with($this->entityMock);
        $oldStartTime = date('m/d/y', time());
        $startTime = date('m/d/y', time() + 24 * 60 * 60);
        $oldId = strtotime($oldStartTime);
        $newId = strtotime($startTime);
        $this->entityMock->expects($this->any())->method('getId')->willReturn($oldId);
        $this->entityMock->expects($this->any())->method('getStartTime')->willReturn($startTime);

        $oldEntityMock = $this->createMock(Update::class);
        $oldEntityMock->expects($this->any())->method('getStartTime')->willReturn($oldStartTime);
        $oldEntityMock->expects($this->atLeastOnce())->method('getId')->willReturn(strtotime($oldStartTime));
        $this->updateFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $oldEntityMock,
                $this->updateMock
            );
        $this->resourceMock->expects($this->any())->method('load');
        $this->updateMock->expects($this->any())->method('getId')->willReturn(null);

        //$this->entityMock->expects($this->once())->method('setOldId')->with(strtotime($oldStartTime));
        $this->entityMock->expects($this->once())->method('getEndTime')->willReturn(null);
        $this->entityMock->expects($this->once())->method('setId')->willReturn($newId);
        $this->entityMock->expects($this->atLeastOnce())->method('getRollbackId')->willReturn(null);
        $this->resourceMock->expects($this->once())->method('save')->with($this->entityMock);

        $this->assertEquals($this->entityMock, $this->model->save($this->entityMock));
    }

    public function testDeleteActiveUpdate()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotDeleteException');
        $this->expectExceptionMessage('The active update can\'t be deleted.');
        $savedVersionId = 123;
        $this->entityMock->expects($this->once())
            ->method('getId')
            ->willReturn($savedVersionId);
        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($savedVersionId);
        $this->entityMock->expects($this->never())
            ->method('delete');

        $this->model->delete($this->entityMock);
    }

    /**
     * Test delete method with rollback assigned to update.
     *
     * @return void
     */
    public function testDeleteUpdateWithRollBackAssigned(): void
    {
        $savedVersionHistoryId = strtotime('+5 minutes');
        $savedVersionId = strtotime('+15 minutes');
        $rollbackId = strtotime('+45 minutes');
        $this->entityMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($savedVersionId);
        $this->entityMock->expects($this->once())
            ->method('getRollbackId')
            ->willReturn($rollbackId);
        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($savedVersionHistoryId);
        $this->resourceMock->expects($this->atleastOnce())
            ->method('getMaxIdByTime')
            ->willReturn($rollbackId + 1);
        $this->resourceMock->expects($this->once())->method('delete');
        $this->resourceMock->expects($this->atLeastOnce())
            ->method('isRollbackAssignedToUpdates')
            ->with($rollbackId, [$savedVersionId])
            ->willReturn(true);
        $this->resourceMock->expects($this->never())->method('load');

        $this->assertTrue($this->model->delete($this->entityMock));
    }

    /**
     * Test delete method without rollback assigned to update.
     *
     * @return void
     */
    public function testDeleteUpdateWithoutRollBackAssigned(): void
    {
        $savedVersionHistoryId = strtotime('+5 minutes');
        $savedVersionId = strtotime('+15 minutes');
        $rollbackId = strtotime('+45 minutes');
        $this->entityMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($savedVersionId);
        $this->entityMock->expects($this->once())
            ->method('getRollbackId')
            ->willReturn($rollbackId);
        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($savedVersionHistoryId);
        $this->resourceMock->expects($this->atleastOnce())
            ->method('getMaxIdByTime')
            ->willReturn($rollbackId + 1);
        $this->resourceMock->expects($this->exactly(2))->method('delete');
        $this->resourceMock->expects($this->atLeastOnce())
            ->method('isRollbackAssignedToUpdates')
            ->with($rollbackId, [$savedVersionId])
            ->willReturn(false);
        $this->resourceMock->expects($this->atLeastOnce())
            ->method('load')
            ->with($this->updateMock, $rollbackId);
        $this->updateMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($savedVersionId);
        $this->updateMock->expects($this->atLeastOnce())
            ->method('getRollbackId')
            ->willReturn(false);
        $this->updateFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->updateMock);

        $this->assertTrue($this->model->delete($this->entityMock));
    }

    public function testShouldTriggerSyncOnEndTimeChange(): void
    {
        $startTimestamp = strtotime('+1 day');
        $oldEndTimestamp = strtotime('+3 days');
        $newEndTimestamp = strtotime('+2 days');
        $startTime = date('Y-m-d H:i:s', $startTimestamp);
        $oldEndTime = date('Y-m-d H:i:s', $oldEndTimestamp);
        $newEndTime = date('Y-m-d H:i:s', $newEndTimestamp);

        $newUpdate = $this->createMock(Update::class);
        $newUpdate->method('getId')
            ->willReturn($startTimestamp);
        $newUpdate->method('getStartTime')
            ->willReturn($startTime);
        $newUpdate->method('getEndTime')
            ->willReturn($newEndTime);

        $oldUpdate = $this->createMock(Update::class);
        $oldUpdate->method('getId')
            ->willReturn($startTimestamp);
        $oldUpdate->method('getStartTime')
            ->willReturn($startTime);
        $oldUpdate->method('getRollbackId')
            ->willReturn($oldEndTime);
        $oldUpdate->method('getEndTime')
            ->willReturn($oldEndTimestamp);

        $rollback = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $getUpdate = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->updateFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->willReturnOnConsecutiveCalls($oldUpdate, $rollback, $getUpdate);
        $this->resourceMock->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive([$newUpdate], [$getUpdate]);

        $this->resourceMock->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([$rollback], [$newUpdate]);

        $this->periodSyncSchedulerMock->expects($this->once())
            ->method('execute')
            ->with([$startTimestamp]);

        $this->resourceMock->expects($this->once())
            ->method('addCommitCallback')
            ->with(
                $this->callback(
                    function (callable $callback) {
                        $callback();
                        return true;
                    }
                )
            );

        $this->assertSame($newUpdate, $this->model->save($newUpdate));
    }
}
