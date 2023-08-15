<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Controller\Adminhtml\Page\Update;

use Magento\Backend\App\Action\Context;
use Magento\CmsStaging\Controller\Adminhtml\Page\Update\Delete as DeleteController;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    /** @var DeleteController */
    protected $controller;

    /** @var Context|MockObject */
    protected $context;

    /** @var \Magento\Staging\Model\Entity\Update\Delete|MockObject */
    protected $stagingUpdateDelete;

    /** @var RequestInterface|MockObject */
    protected $request;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMock();
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->stagingUpdateDelete = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Delete::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->controller = new DeleteController($this->context, $this->stagingUpdateDelete);
    }

    public function testExecute()
    {
        $pageId = 1;
        $updateId = 2;
        $staging = [];
        $this->request->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(
                ['page_id'],
                ['update_id'],
                ['staging']
            )
            ->willReturnOnConsecutiveCalls(
                $pageId,
                $updateId,
                $staging
            );
        $this->stagingUpdateDelete
            ->expects($this->once())
            ->method('execute')
            ->with([
                'entityId' => $pageId,
                'updateId' => $updateId,
                'stagingData' => $staging
            ])
            ->willReturn(true);
        $this->assertTrue($this->controller->execute());
    }
}
