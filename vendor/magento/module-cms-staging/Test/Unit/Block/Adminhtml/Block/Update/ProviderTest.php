<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Block\Adminhtml\Block\Update;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\CmsStaging\Block\Adminhtml\Block\Update\Provider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $blockRepositoryMock;

    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var Provider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->blockRepositoryMock = $this->getMockForAbstractClass(BlockRepositoryInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);

        $this->provider = new Provider(
            $this->requestMock,
            $this->blockRepositoryMock
        );
    }

    public function testGetIdReturnsBlockIdIfBlockExists()
    {
        $blockId = 1;

        $blockMock = $this->getMockForAbstractClass(BlockInterface::class);
        $blockMock->expects($this->any())->method('getId')->willReturn($blockId);

        $this->requestMock->expects($this->any())->method('getParam')->with('block_id')->willReturn($blockId);
        $this->blockRepositoryMock->expects($this->any())->method('getById')->with($blockId)->willReturn($blockMock);

        $this->assertEquals($blockId, $this->provider->getId());
    }

    public function testGetIdReturnsNullIfBlockDoesNotExist()
    {
        $blockId = 9999;

        $this->requestMock->expects($this->any())->method('getParam')->with('block_id')->willReturn($blockId);
        $this->blockRepositoryMock->expects($this->any())
            ->method('getById')
            ->with($blockId)
            ->willThrowException(NoSuchEntityException::singleField('block_id', $blockId));

        $this->assertNull($this->provider->getId());
    }

    public function testGetUrlReturnsNull()
    {
        $this->assertNull($this->provider->getUrl(1));
    }
}
