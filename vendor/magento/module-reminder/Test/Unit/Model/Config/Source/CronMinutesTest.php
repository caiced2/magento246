<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Model\Config\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reminder\Model\Config\Source\CronMinutes;
use PHPUnit\Framework\TestCase;

class CronMinutesTest extends TestCase
{
    /**
     * @var CronMinutes
     */
    private $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->model = $helper->getObject(CronMinutes::class);
    }

    /**
     * @return void
     */
    public function testGetCronMinutes()
    {
        $expected = [
            5 => __('5 minutes'),
            10 => __('10 minutes'),
            15 => __('15 minutes'),
            20 => __('20 minutes'),
            30 => __('30 minutes'),
        ];

        $this->assertEquals($expected, $this->model->getCronMinutes());
    }
}
