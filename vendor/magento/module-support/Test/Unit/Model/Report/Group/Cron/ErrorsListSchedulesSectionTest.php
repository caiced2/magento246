<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Cron;

use Magento\Cron\Model\Schedule;
use Magento\Support\Model\Report\Group\Cron\ErrorsListSchedulesSection;
use PHPUnit\Framework\MockObject_MockObject as ObjectMock;

class ErrorsListSchedulesSectionTest extends AbstractListSchedulesSectionTest
{
    /**
     * @var ErrorsListSchedulesSection
     */
    protected $report;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->report = $this->objectManagerHelper->getObject(
            ErrorsListSchedulesSection::class,
            [
                'scheduleCollectionFactory' => $this->scheduleCollectionFactoryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGenerate()
    {
        $this->scheduleCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('status', Schedule::STATUS_ERROR)
            ->willReturnSelf();
        $this->scheduleCollectionMock->expects($this->once())
            ->method('load')
            ->willReturn([
                $this->getScheduleMock(
                    1,
                    'clear_cache',
                    'error',
                    'First error',
                    '01.01.1970 00:01',
                    '01.01.1970 00:01',
                    '01.01.1970 00:01',
                    null
                ),
                $this->getScheduleMock(
                    2,
                    'tax_reindex',
                    'error',
                    'Error of reindex',
                    '01.01.1970 00:01',
                    '01.01.1970 00:01',
                    '01.01.1970 00:01',
                    null
                ),
                $this->getScheduleMock(
                    3,
                    'clear_cache',
                    'error',
                    'Second error',
                    '02.01.1970 00:01',
                    '02.01.1970 00:01',
                    '02.01.1970 00:01',
                    null
                ),
                $this->getScheduleMock(
                    4,
                    'tax_reindex',
                    'error',
                    'Error of reindex',
                    '02.01.1970 00:01',
                    '02.01.1970 00:01',
                    '02.01.1970 00:01',
                    null
                )
            ]);

        $data = [
            [
                1, 'clear_cache', 'First error', 1,
                '01.01.1970 00:01', '01.01.1970 00:01', '01.01.1970 00:01', null
            ],
            [
                3, 'clear_cache', 'Second error', 1,
                '02.01.1970 00:01', '02.01.1970 00:01', '02.01.1970 00:01', null
            ],
            [
                4, 'tax_reindex', 'Error of reindex', 2,
                '02.01.1970 00:01', '02.01.1970 00:01', '02.01.1970 00:01', null
            ]
        ];

        $this->setExpectedResult($data);
    }

    /**
     * @return void
     */
    public function testGenerateWithCollectionException()
    {
        $e = new \Exception();
        $this->scheduleCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->willThrowException($e);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($e);

        $this->setExpectedResult();
    }

    /**
     * @return void
     */
    public function testGenerateWithScheduleModelException()
    {
        $e = new \Exception();
        $this->scheduleCollectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('status', Schedule::STATUS_ERROR)
            ->willReturnSelf();

        /** @var \Magento\Cron\Model\Schedule|ObjectMock $scheduleMock */
        $scheduleMock = $this->getMockBuilder(Schedule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $scheduleMock->expects($this->once())
            ->method('getId')
            ->willThrowException($e);

        $this->scheduleCollectionMock->expects($this->once())
            ->method('load')
            ->willReturn([$scheduleMock]);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($e);

        $this->setExpectedResult();
    }

    /**
     * @param array $data
     * @return void
     */
    protected function setExpectedResult($data = [])
    {
        $expectedResult = [
            'Errors in Cron Schedules Queue' => [
                'headers' => [
                    __('Schedule Id'), __('Job Code'), __('Error'), __('Count'),
                    __('Created At'), __('Scheduled At'), __('Executed At'), __('Finished At')
                ],
                'data' => $data
            ]
        ];

        $this->assertEquals($expectedResult, $this->report->generate());
    }
}
