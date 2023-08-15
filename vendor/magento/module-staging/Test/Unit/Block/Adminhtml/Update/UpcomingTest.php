<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;
use Magento\Staging\Block\Adminhtml\Update\Upcoming;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpcomingTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $entityProviderMock;

    /**
     * @var MockObject
     */
    protected $contextMock;

    /**
     * @var MockObject
     */
    protected $layoutMock;

    /**
     * @var AuthorizationInterface|MockObject
     */
    protected $authorization;

    /**
     * @var Upcoming
     */
    protected $block;

    protected function setUp(): void
    {
        $this->entityProviderMock = $this->createMock(
            EntityProviderInterface::class
        );

        $this->contextMock = $this->createMock(Context::class);
        $this->layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->contextMock->expects($this->once())->method('getLayout')->willReturn($this->layoutMock);
        $this->authorization = $this->getMockForAbstractClass(AuthorizationInterface::class);

        $this->block = new Upcoming(
            $this->contextMock,
            $this->entityProviderMock,
            $this->authorization
        );
    }

    public function testToHtmlNoId()
    {
        $this->entityProviderMock->expects($this->once())->method('getId')->willReturn(null);
        $this->layoutMock->expects($this->never())->method('getChildNames');
        $this->assertEmpty($this->block->toHtml());
    }

    public function testToHtml()
    {
        $rendered = 'hop';

        $this->entityProviderMock->expects($this->once())->method('getId')->willReturn(123);
        $this->layoutMock->expects($this->atLeastOnce())->method('getChildNames')->willReturn([1]);
        $this->layoutMock->expects($this->atLeastOnce())->method('renderElement')->willReturn($rendered);

        $this->assertEquals($rendered, $this->block->toHtml());
    }
}
