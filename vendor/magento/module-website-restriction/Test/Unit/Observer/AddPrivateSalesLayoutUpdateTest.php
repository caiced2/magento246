<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WebsiteRestriction\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Layout\ProcessorInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\WebsiteRestriction\Model\ConfigInterface;
use Magento\WebsiteRestriction\Observer\AddPrivateSalesLayoutUpdate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddPrivateSalesLayoutUpdateTest extends TestCase
{
    /**
     * @var \Magento\WebsiteRestriction\Model\Observer\AddPrivateSalesLayoutUpdate
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $configMock;

    /**
     * @var MockObject
     */
    protected $updateMock;

    /**
     * @var MockObject
     */
    protected $observer;

    protected function setUp(): void
    {
        $this->configMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->updateMock = $this->getMockForAbstractClass(ProcessorInterface::class);
        $this->observer = $this->createMock(Observer::class);

        $layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $layoutMock->expects($this->any())->method('getUpdate')->willReturn($this->updateMock);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getLayout'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->any())->method('getLayout')->willReturn($layoutMock);

        $this->observer->expects($this->any())->method('getEvent')->willReturn($eventMock);
        $this->model = new AddPrivateSalesLayoutUpdate($this->configMock);
    }

    public function testExecuteSuccess()
    {
        $this->configMock->expects($this->once())->method('getMode')->willReturn(1);
        $this->updateMock->expects($this->once())->method('addHandle')->with('restriction_privatesales_mode');
        $this->model->execute($this->observer);
    }

    public function testExecuteWithStrictType()
    {
        $this->configMock->expects($this->once())->method('getMode')->willReturn('1');
        $this->updateMock->expects($this->never())->method('addHandle');
        $this->model->execute($this->observer);
    }

    public function testExecuteWithNonAllowedMode()
    {
        $this->configMock->expects($this->once())->method('getMode')->willReturn('some mode');
        $this->updateMock->expects($this->never())->method('addHandle');
        $this->model->execute($this->observer);
    }
}
