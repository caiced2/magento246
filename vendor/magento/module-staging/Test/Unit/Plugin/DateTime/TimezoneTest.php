<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Plugin\DateTime;

use Magento\Staging\Model\VersionManager;
use Magento\Staging\Plugin\DateTime\Timezone;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TimezoneTest extends TestCase
{
    /**
     * @var Timezone
     */
    private $plugin;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    protected function setUp(): void
    {
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new Timezone($this->versionManager);
    }

    /**
     * @param bool $isPreviewVersion
     * @param bool $isScopeDateInInterval
     * @dataProvider dataProvider
     */
    public function testAfterIsScopeDateInInterval(
        $isPreviewVersion,
        $isScopeDateInInterval,
        $expected
    ) {
        $timezoneMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\Timezone::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->versionManager->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn($isPreviewVersion);

        $result = $this->plugin->afterIsScopeDateInInterval($timezoneMock, $isScopeDateInInterval);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [false, true, true],
            [false, false, false],
            [true, true, true],
            [true, false, true],
        ];
    }
}
