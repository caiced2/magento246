<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Controller\Adminhtml\Category\Update;

use Magento\Backend\App\Action\Context;
use Magento\CatalogStaging\Controller\Adminhtml\Category\Update\Delete as DeleteController;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Model\VersionManager;
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
        $categoryId = 1;
        $updateId = 2;
        $staging = ['mode' => 'remove'];

        $this->request->method('getParam')
            ->willReturnMap([
                ['id', null, $categoryId],
                ['staging', null, $staging],
                ['update_id', null, $updateId],
            ]);
        $this->request->expects($this->once())
            ->method('setParams')
            ->with([VersionManager::PARAM_NAME => $updateId]);

        $this->stagingUpdateDelete
            ->expects($this->once())
            ->method('execute')
            ->with([
                'entityId' => $categoryId,
                'updateId' => $updateId,
                'stagingData' => $staging
            ])
            ->willReturn(true);
        $this->assertTrue($this->controller->execute());
    }
}
