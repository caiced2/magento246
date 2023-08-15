<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Plugin\PageCache\Model;

use Magento\Staging\Model\VersionManager;
use Magento\Staging\Plugin\PageCache\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Page Cache config plugin.
 */
class ConfigTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var Config
     */
    private $subject;

    /**
     * @var \Magento\PageCache\Model\Config|MockObject
     */
    private $configMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(\Magento\PageCache\Model\Config::class);

        $this->versionManagerMock = $this->createMock(VersionManager::class);

        $this->subject = new Config(
            $this->versionManagerMock
        );
    }

    public function testAfterIsEnabledPreview()
    {
        $isEnabled = true;

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->assertFalse($this->subject->afterIsEnabled($this->configMock, $isEnabled));
    }

    public function testAfterIsEnabledNotPreview()
    {
        $isEnabled = true;

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(false);

        $this->assertEquals($isEnabled, $this->subject->afterIsEnabled($this->configMock, $isEnabled));
    }
}
