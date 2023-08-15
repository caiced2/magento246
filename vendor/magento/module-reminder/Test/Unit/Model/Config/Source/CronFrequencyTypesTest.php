<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Model\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reminder\Model\Config\Source\CronFrequencyTypes;
use PHPUnit\Framework\TestCase;

class CronFrequencyTypesTest extends TestCase
{
    /**
     * @var CronFrequencyTypes
     */
    private $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->model = $helper->getObject(CronFrequencyTypes::class);
    }

    /**
     * @return void
     */
    public function testGetCronFrequencyTypes()
    {
        $expected = [
            CronFrequencyTypes::CRON_MINUTELY => __('Minute Intervals'),
            CronFrequencyTypes::CRON_HOURLY => __('Hourly'),
            CronFrequencyTypes::CRON_DAILY => __('Daily'),
        ];

        $this->assertEquals($expected, $this->model->getCronFrequencyTypes());
    }
}
