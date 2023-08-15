<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Controller\Adminhtml\Report\Delete;
use Magento\Support\Model\Report;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\ReportFactory|MockObject
     */
    protected $reportFactoryMock;

    /**
     * @var Report|MockObject
     */
    protected $reportMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Delete
     */
    protected $deleteAction;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->reportMock = $this->createMock(Report::class);

        $this->reportFactoryMock = $this->createPartialMock(\Magento\Support\Model\ReportFactory::class, ['create']);
        $this->reportFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->reportMock);

        $this->resultRedirectMock = $this->createMock(Redirect::class);
        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirectMock);

        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);

        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->deleteAction = $this->objectManagerHelper->getObject(
            Delete::class,
            [
                'context' => $this->contextMock,
                'reportFactory' => $this->reportFactoryMock
            ]
        );
    }

    /**
     * @param int $id
     * @return void
     */
    protected function setIdReportForTests($id)
    {
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id', 0)
            ->willReturn($id);
        $this->reportMock->expects($this->once())
            ->method('load')
            ->with($id)
            ->willReturnSelf();
        $this->reportMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
    }

    /**
     * @return void
     */
    public function testExecuteWithoutReport()
    {
        $this->setIdReportForTests(0);

        $this->reportMock->expects($this->never())
            ->method('delete');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Unable to find a system report to delete.'))
            ->willReturnSelf();

        $this->assertSame($this->resultRedirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteMainFlow()
    {
        $this->setIdReportForTests(1);
        $this->reportMock->expects($this->once())
            ->method('delete');

        $this->messageManagerMock->expects($this->never())
            ->method('addError')
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('The system report has been deleted.'))
            ->willReturnSelf();

        $this->assertSame($this->resultRedirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteGetWithLocalizedException()
    {
        $errorMsg = 'Test error';
        $this->setIdReportForTests(1);
        $this->reportMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new LocalizedException(__($errorMsg)));
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with($errorMsg)
            ->willReturnSelf();
        $this->assertSame($this->resultRedirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteGetWithException()
    {
        $e = new \Exception();
        $this->setIdReportForTests(1);
        $this->reportMock->expects($this->once())
            ->method('delete')
            ->willThrowException($e);
        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with(
                $e,
                __('An error occurred while deleting the system report. Please review log and try again.')
            )
            ->willReturnSelf();
        $this->assertSame($this->resultRedirectMock, $this->deleteAction->execute());
    }
}
