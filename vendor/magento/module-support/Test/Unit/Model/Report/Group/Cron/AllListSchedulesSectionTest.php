<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Cron;

use Magento\Cron\Model\Schedule;
use Magento\Support\Model\Report\Group\Cron\AllListSchedulesSection;
use PHPUnit\Framework\MockObject_MockObject as ObjectMock;

class AllListSchedulesSectionTest extends AbstractListSchedulesSectionTest
{
    /**
     * @var AllListSchedulesSection
     */
    protected $report;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->report = $this->objectManagerHelper->getObject(
            AllListSchedulesSection::class,
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
            ->method('load')
            ->willReturn([
                $this->getScheduleMock(
                    1,
                    'clear_cache',
                    'error',
                    null,
                    '01.01.1970 00:01',
                    '01.01.1970 00:01',
                    '01.01.1970 00:01',
                    null
                ),
                $this->getScheduleMock(
                    2,
                    'tax_reindex',
                    'pending',
                    null,
                    '01.01.1970 00:01',
                    '01.01.1970 00:01',
                    null,
                    null
                ),
                $this->getScheduleMock(
                    3,
                    'clear_cache',
                    'success',
                    null,
                    '02.01.1970 00:01',
                    '02.01.1970 00:01',
                    '02.01.1970 00:01',
                    '02.01.1970 00:09'
                )
            ]);

        $data = [
            [
                1, 'clear_cache', 'error', '01.01.1970 00:01',
                '01.01.1970 00:01', '01.01.1970 00:01', null
            ],
            [
                2, 'tax_reindex', 'pending', '01.01.1970 00:01',
                '01.01.1970 00:01', null, null
            ],
            [
                3, 'clear_cache', 'success', '02.01.1970 00:01',
                '02.01.1970 00:01', '02.01.1970 00:01', '02.01.1970 00:09'
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
            ->method('load')
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

        /** @var Schedule|ObjectMock $scheduleMock */
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
            'Cron Schedules List' => [
                'headers' => [
                    __('Schedule Id'), __('Job Code'), __('Status'), __('Created At'),
                    __('Scheduled At'), __('Executed At'), __('Finished At')
                ],
                'data' => $data
            ]
        ];

        $this->assertEquals($expectedResult, $this->report->generate());
    }
}
