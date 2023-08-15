<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update\Entity;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\Item;
use Magento\Backend\Block\Widget\Button\ToolbarInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\Toolbar;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ToolbarTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $contextMock;

    /**
     * @var Toolbar
     */
    protected $toolbar;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var MockObject
     */
    protected $buttonListMock;

    /**
     * @var MockObject
     */
    protected $toolbarMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);

        $this->buttonListMock = $this->createMock(ButtonList::class);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getButtonList')
            ->willReturn($this->buttonListMock);

        $this->toolbarMock = $this->getMockForAbstractClass(ToolbarInterface::class);
        $this->contextMock->expects($this->once())
            ->method('getButtonToolbar')
            ->willReturn($this->toolbarMock);
        $this->data = [];
        $this->toolbar = new Toolbar(
            $this->contextMock,
            $this->data
        );
    }

    public function testUpdateButton()
    {
        $buttonId = '123';
        $key = '456';
        $data = '2341';
        $this->buttonListMock->expects($this->once())
            ->method('update')
            ->with($buttonId, $key, $data);
        $this->toolbar->updateButton($buttonId, $key, $data);
    }

    public function testPrepareLayout()
    {
        $layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->buttonListMock->expects($this->once())->method('add');
        $this->toolbarMock->expects($this->once())->method('pushButtons');
        $this->toolbar->setLayout($layoutMock);
    }

    public function testAddButton()
    {
        $buttonId = 'LuckyId';
        $data = [300, 20, 30];
        $level = 100;
        $sortOrder = 330;
        $region = 'SomePlace';

        $this->buttonListMock->expects($this->once())
            ->method('add')
            ->with($buttonId, $data, $level, $sortOrder, $region);
        $this->toolbar->addButton($buttonId, $data, $level, $sortOrder, $region);
    }

    public function testRemoveButton()
    {
        $buttonId = 'HopHey';

        $this->buttonListMock->expects($this->once())
            ->method('remove')
            ->with($buttonId);
        $this->toolbar->removeButton($buttonId);
    }

    public function testCanRender()
    {
        $itemMock = $this->createMock(Item::class);
        $itemMock->expects($this->once())->method('isDeleted')->willReturn(true);
        $this->assertFalse($this->toolbar->canRender($itemMock));
    }
}
