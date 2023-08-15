<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Controller\Adminhtml\Entity\Update;

use Magento\Framework\EntityManager\Hydrator;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Controller\Adminhtml\Entity\Update\Service;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{

    /**
     * @var MockObject
     */
    private $metadataPoolMock;

    /**
     * @var MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var MockObject
     */
    private $updateFactoryMock;

    /**
     * @var MockObject
     */
    private $versionManagerMock;

    /**
     * @var Service
     */
    private $service;

    protected function setUp(): void
    {
        $this->metadataPoolMock =
            $this->createMock(MetadataPool::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);
        $this->updateFactoryMock =
            $this->createPartialMock(UpdateFactory::class, ['create']);
        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->service = new Service(
            $this->metadataPoolMock,
            $this->updateRepositoryMock,
            $this->updateFactoryMock,
            $this->versionManagerMock
        );
    }

    public function testCreateUpdateCreatesUpdate()
    {
        $updateData = [];
        $updateMock = $this->createMock(Update::class);
        $hydratorMock = $this->createPartialMock(
            Hydrator::class,
            ['extract', 'hydrate']
        );

        $this->updateFactoryMock->expects($this->once())->method('create')->willReturn($updateMock);
        $this->metadataPoolMock->expects($this->any())
            ->method('getHydrator')
            ->with(UpdateInterface::class)
            ->willReturn($hydratorMock);
        $hydratorMock->expects($this->once())->method('hydrate')->with($updateMock, $updateData);
        $updateMock->expects($this->once())->method('setIsCampaign')->with(false);
        $this->updateRepositoryMock->expects($this->once())->method('save')->with($updateMock);

        $this->assertEquals($updateMock, $this->service->createUpdate($updateData));
    }

    public function testEditUpdateSavesEditedUpdate()
    {
        $updateId = 1;
        $startTime = '01/24/2016 09:00';
        $endTime = '02/24/2016 09:00';
        $updateData = [
            'update_id' => $updateId,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        $updateMock = $this->createMock(Update::class);
        $hydratorMock = $this->createPartialMock(
            Hydrator::class,
            ['extract', 'hydrate']
        );

        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);
        $updateMock->expects($this->exactly(2))->method('getStartTime')->willReturn($startTime);
        $updateMock->expects($this->exactly(2))->method('getEndTime')->willReturn($endTime);
        $this->metadataPoolMock->expects($this->atLeastOnce())
            ->method('getHydrator')
            ->with(UpdateInterface::class)
            ->willReturn($hydratorMock);
        $hydratorMock->expects($this->once())->method('hydrate')->with($updateMock, $updateData);
        $this->updateRepositoryMock->expects($this->once())->method('save')->with($updateMock);

        $this->assertEquals($updateMock, $this->service->editUpdate($updateData));
    }

    public function testEditUpdateSavesCreatesUpdate()
    {
        $updateId = 1;
        $startTime = '01/24/2016 09:00';
        $endTime = '02/24/2016 09:00';
        $updateStart = '02/24/2016 10:00';
        $updateData = [
            'update_id' => $updateId,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
        $updateMock = $this->createMock(Update::class);
        $hydratorMock = $this->createPartialMock(
            Hydrator::class,
            ['extract', 'hydrate']
        );

        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);
        $updateMock->expects($this->exactly(2))->method('getStartTime')->willReturn($updateStart);
        $this->metadataPoolMock->expects($this->once())
            ->method('getHydrator')
            ->with(UpdateInterface::class)
            ->willReturn($hydratorMock);

        $this->updateFactoryMock->expects($this->once())->method('create')->willReturn($updateMock);
        $this->assertEquals($updateMock, $this->service->editUpdate($updateData));
    }

    public function testAssignedUpdateRetrievesCorrespondingUpdateIfInputParametersAreValid()
    {
        $updateId = 1;
        $updateData = [
            'select_id' => [
                [
                    'id' => $updateId,
                ],
            ],
        ];
        $updateMock = $this->createMock(Update::class);
        $this->updateRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->with($updateData['select_id'])
            ->willReturn($updateMock);

        $this->assertEquals($updateMock, $this->service->assignUpdate($updateData));
    }

    public function testAssignedUpdateThrowsExceptionIfInputParametersAreInvalid()
    {
        $this->expectException('OutOfBoundsException');
        $this->expectExceptionMessage('The \'select_id\' value is required.');
        $updateData = [];
        $this->updateRepositoryMock->expects($this->never())->method('get');

        $this->service->assignUpdate($updateData);
    }

    /**
     * @return array
     */
    public function prepareDataForEditUpdateSavesEditedUpdate()
    {
        return [
            //changing only start time
            ['01/24/2016 09:00', '03/24/2016 09:00', '02/24/2016 09:00', '03/24/2016 09:00', true],
            //changing only end time
            ['01/24/2016 09:00', '03/24/2016 09:00', '01/24/2016 09:00', '04/24/2016 09:00', true],
            //changing both times
            ['01/24/2016 09:00', '03/24/2016 09:00', '02/24/2016 09:00', '04/24/2016 09:00', true],
            //change nothings
            ['01/24/2016 09:00', '03/24/2016 09:00', '01/24/2016 09:00', '03/24/2016 09:00', false],
        ];
    }

    /**
     * @param string $startTime
     * @param string $endTime
     * @param string $updateStartTime
     * @param string $updateEndTime
     * @param boolean $shouldCreateUpdate
     * @dataProvider prepareDataForEditUpdateSavesEditedUpdate
     */
    public function testEditUpdate($startTime, $endTime, $updateStartTime, $updateEndTime, $shouldCreateUpdate)
    {
        $updateId = 1;
        $updateData = [
            'update_id' => $updateId,
            'start_time' => $updateStartTime,
            'end_time' => $updateEndTime
        ];
        $update = $this->getMockBuilder(UpdateInterface::class)
            ->getMock();
        $this->updateRepositoryMock
            ->expects($this->once())
            ->method("get")
            ->with($updateId)
            ->willReturn($update);

        $update
            ->expects($this->atLeastOnce())
            ->method("getStartTime")
            ->willReturn($startTime);

        $update
            ->expects($this->any())
            ->method("getEndTime")
            ->willReturn($endTime);
        //Handle Hydrator
        $hydratorMock = $this->createPartialMock(
            Hydrator::class,
            ['extract', 'hydrate']
        );

        $this->metadataPoolMock->expects($this->once())
            ->method('getHydrator')
            ->with(UpdateInterface::class)
            ->willReturn($hydratorMock);

        //Main Logic: Whether we should edit our update or drop exist and create new one
        if ($shouldCreateUpdate) {
            $this->updateFactoryMock
                ->expects($this->once())
                ->method("create")
                ->willReturn($update);
        } else {
            $this->updateFactoryMock
                ->expects($this->never())
                ->method("create");
        }

        $this->updateRepositoryMock
            ->expects($this->once())
            ->method("save")
            ->with($update);

        $this->assertEquals($update, $this->service->editUpdate($updateData));
    }
}
