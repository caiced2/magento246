<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Group\Cron\AbstractCronJobsSection;
use Magento\Support\Model\Report\Group\Cron\CronJobs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractCronJobSectionTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var CronJobs|MockObject
     */
    protected $cronJobsMock;

    /**
     * @var AbstractCronJobsSection
     */
    protected $report;

    /**
     * @var array
     */
    protected $cronJobs = [
        'clear_cache' => [
            'name' => 'clear_cache',
            'expression' => '*/1 * * * *',
            'instance' => 'Vendor\Module\Class',
            'method' => 'clear',
            'group_code' => 'default'
        ]
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->cronJobsMock = $this->createMock(CronJobs::class);

        $this->cronJobsMock->expects($this->once())
            ->method('getCronInformation')
            ->with($this->cronJobs['clear_cache'])
            ->willReturn($this->cronJobs['clear_cache']);
    }

    /**
     * @param string $title
     * @param array $data
     * @return array
     */
    protected function getExpectedResult($title, $data)
    {
        return [
            $title => [
                'headers' => [
                    __('Job Code'), __('Cron Expression'), __('Run Class'), __('Run Method'), __('Group Code')
                ],
                'data' => $data
            ]
        ];
    }
}
