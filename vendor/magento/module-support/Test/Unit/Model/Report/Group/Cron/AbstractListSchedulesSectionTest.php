<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Cron;

use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Cron\Model\Schedule;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject as ObjectMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class AbstractListSchedulesSectionTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory|ObjectMock
     */
    protected $scheduleCollectionFactoryMock;

    /**
     * @var LoggerInterface|ObjectMock
     */
    protected $loggerMock;

    /**
     * @var Collection|ObjectMock
     */
    protected $scheduleCollectionMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->scheduleCollectionFactoryMock = $this->createPartialMock(
            \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory::class,
            ['create']
        );
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->scheduleCollectionMock = $this->getMockBuilder(
            Collection::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->scheduleCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->scheduleCollectionMock);
    }

    /**
     * @param int $id
     * @param string $jobCode
     * @param string $status
     * @param string $message
     * @param string $createdAt
     * @param string $scheduledAt
     * @param string $executedAt
     * @param string $finishedAt
     * @return Schedule|ObjectMock
     */
    protected function getScheduleMock(
        $id,
        $jobCode,
        $status,
        $message,
        $createdAt,
        $scheduledAt,
        $executedAt,
        $finishedAt
    ) {
        /** @var Schedule|ObjectMock $scheduleMock */
        $scheduleMock = $this->getMockBuilder(Schedule::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId', 'getJobCode', 'getCreatedAt', 'getScheduledAt',
                'getExecutedAt', 'getFinishedAt', 'getStatus', 'getMessages'
            ])
            ->getMock();
        $scheduleMock->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $scheduleMock->expects($this->any())
            ->method('getJobCode')
            ->willReturn($jobCode);
        $scheduleMock->expects($this->any())
            ->method('getStatus')
            ->willReturn($status);
        $scheduleMock->expects($this->any())
            ->method('getMessages')
            ->willReturn($message);
        $scheduleMock->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn($createdAt);
        $scheduleMock->expects($this->any())
            ->method('getScheduledAt')
            ->willReturn($scheduledAt);
        $scheduleMock->expects($this->any())
            ->method('getExecutedAt')
            ->willReturn($executedAt);
        $scheduleMock->expects($this->any())
            ->method('getFinishedAt')
            ->willReturn($finishedAt);

        return $scheduleMock;
    }
}
