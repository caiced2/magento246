<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\CacheState;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CacheStateTest extends TestCase
{
    /**
     * @var CacheState
     */
    private $cacheState;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var StateInterface|MockObject
     */
    private $stateMock;

    protected function setUp(): void
    {
        $this->stateMock = $this->getMockBuilder(StateInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = new ObjectManager($this);
        $this->cacheState = $objectManager->getObject(
            CacheState::class,
            [
                'state' => $this->stateMock,
                'versionManager' => $this->versionManagerMock,
                'cacheTypes' => [
                    'block_html' => false
                ],
            ]
        );
    }

    public function testIsPreviewCacheDisabled()
    {
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->assertFalse($this->cacheState->isEnabled('block_html'));
    }

    public function testIsPreviewCacheEnabled()
    {
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with('ddl_cache')
            ->willReturn(true);
        $this->assertTrue($this->cacheState->isEnabled('ddl_cache'));
    }

    public function testIsNotPreviewCacheEnabled()
    {
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(false);
        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with('block_html')
            ->willReturn(true);
        $this->assertTrue($this->cacheState->isEnabled('block_html'));
    }
}
