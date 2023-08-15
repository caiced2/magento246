<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\Report\Config;
use Magento\Support\Model\Report\Source\ReportGroups;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReportGroupsTest extends TestCase
{
    /** @var ReportGroups */
    protected $reportGroups;

    /** @var ObjectManager */
    protected $objectManager;

    /** @var Config|MockObject */
    protected $config;

    /** @var array */
    protected $options = [];

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->config = $this->createMock(Config::class);

        $this->reportGroups = $this->objectManager->getObject(
            ReportGroups::class,
            [
                'config' => $this->config,
            ]
        );
    }

    /**
     * @return void
     */
    public function testSelectedDelete()
    {
        $generalLabel = __('General');
        $environmentLabel = __('Environment');
        $groupOptions = [
            [
                'value' => 'general',
                'label' => $generalLabel
            ],
            [
                'value' => 'environment',
                'label' => $environmentLabel
            ]
        ];

        $expectedOptions = [
            [
                'label' => '',
                'value' => ''
            ],
            [
                'value' => 'general',
                'label' => $generalLabel
            ],
            [
                'value' => 'environment',
                'label' => $environmentLabel
            ],
        ];

        $this->config->expects($this->once())->method('getGroupOptions')->willReturn($groupOptions);

        $this->assertSame($expectedOptions, $this->reportGroups->toOptionArray());
    }
}
