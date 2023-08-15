<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Cron;

use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Group\Cron\CountCodesSchedulesSection;
use PHPUnit\Framework\MockObject\MockObject as ObjectMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CountCodesSchedulesSectionTest extends TestCase
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
     * @var CountCodesSchedulesSection
     */
    protected $report;

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

        $this->report = $this->objectManagerHelper->getObject(
            CountCodesSchedulesSection::class,
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
        $table = 'cron_schedule';
        $sql = "SELECT COUNT( * ) AS `cnt`, `job_code`
                FROM `" . $table . "`
                GROUP BY `job_code`
                ORDER BY `job_code`";
        $result = [
            ['job_code' => 'clear_cache', 'cnt' => 1],
            ['job_code' => 'tax_index', 'cnt' => 2],
        ];

        /** @var AdapterInterface|ObjectMock $adapter */
        $adapter = $this->getMockForAbstractClass(AdapterInterface::class);
        $adapter->expects($this->once())
            ->method('fetchAll')
            ->with($sql)
            ->willReturn($result);

        /** @var AbstractDb|ObjectMock $abstractDb */
        $abstractDb = $this->getMockBuilder(AbstractDb::class)
            ->setMethods(['getConnection', 'getTable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $abstractDb->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapter);

        $abstractDb->expects($this->once())
            ->method('getTable')
            ->willReturn($table);

        /** @var Collection|ObjectMock $collection */
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('getResource')
            ->willReturn($abstractDb);

        $this->scheduleCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collection);

        $this->setExpectedResult([['clear_cache', 1], ['tax_index', 2]]);
    }

    /**
     * @return void
     */
    public function testGenerateWithException()
    {
        $e = new \Exception('Test exception');
        $this->scheduleCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willThrowException($e);
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
            'Cron Schedules by job code' => [
                'headers' => [__('Job Code'), __('Count')],
                'data' => $data
            ]
        ];
        $this->assertEquals($expectedResult, $this->report->generate());
    }
}
