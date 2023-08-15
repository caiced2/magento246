<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Action;
use Magento\Rma\Model\Rma\Source\Status;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Action
 */
class ActionTest extends TestCase
{
    /**
     * @var Action
     */
    protected $action;

    /**
     * @var MockObject
     */
    protected $columnMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->columnMock = $this->getMockBuilder(Column::class)
            ->disableOriginalConstructor()
            ->setMethods(['getActions'])
            ->getMock();
        $this->action = $objectManager->getObject(
            Action::class,
            []
        );
    }

    public function testRenderNoActions()
    {
        $this->columnMock->expects($this->once())
            ->method('getActions')
            ->willReturn('');
        $row = new DataObject();
        $this->action->setColumn($this->columnMock);
        $this->assertEquals('&nbsp;', $this->action->render($row));
    }

    public function testRender()
    {
        $actions = [['caption' => 'Details']];
        $this->columnMock->expects($this->once())
            ->method('getActions')
            ->willReturn($actions);
        $row = new DataObject();
        $row->setStatus(Status::STATE_APPROVED);
        $this->action->setColumn($this->columnMock);
        $result = $this->action->render($row);
        $result = explode('<input', $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertStringContainsString('rma-action-links', $result[2]);
    }
}
