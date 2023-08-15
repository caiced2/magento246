<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Backup;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Controller\Adminhtml\Backup\Delete;
use Magento\Support\Model\Backup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\BackupFactory|MockObject ;
     */
    protected $backupFactoryMock;

    /**
     * @var Backup|MockObject
     */
    protected $backupMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var Redirect|MockObject
     */
    protected $redirectMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var Delete
     */
    protected $deleteAction;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var int
     */
    protected $id = 1;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id', 0)
            ->willReturn($this->id);

        $this->backupMock = $this->createMock(Backup::class);
        $this->backupMock->expects($this->once())
            ->method('load')
            ->with($this->id)
            ->willReturnSelf();
        $this->backupFactoryMock = $this->createPartialMock(\Magento\Support\Model\BackupFactory::class, ['create']);
        $this->backupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->backupMock);

        $this->redirectMock = $this->createMock(Redirect::class);
        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->redirectMock);

        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'messageManager' => $this->messageManagerMock,
                'resultFactory' => $this->resultFactoryMock,
                'request' => $this->requestMock
            ]
        );
        $this->deleteAction = $this->objectManagerHelper->getObject(
            Delete::class,
            [
                'context' => $this->context,
                'backupFactory' => $this->backupFactoryMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $this->backupMock->expects($this->once())
            ->method('getId')
            ->willReturn($this->id);
        $this->backupMock->expects($this->once())
            ->method('delete')
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('The backup has been deleted.'))
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWrongId()
    {
        $this->backupMock->expects($this->once())
            ->method('getId')
            ->willReturn(0);
        $this->backupMock->expects($this->never())->method('delete');
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('Wrong param id'))
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $eText = 'Some error';
        $e = new LocalizedException(__($eText));
        $this->backupMock->expects($this->once())
            ->method('getId')
            ->willReturn($this->id);
        $this->backupMock->expects($this->once())
            ->method('delete')
            ->willThrowException($e);
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with($eText)
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->deleteAction->execute());
    }

    /**
     * @return void
     */
    public function testExecuteWithException()
    {
        $e = new \Exception();
        $this->backupMock->expects($this->once())
            ->method('getId')
            ->willReturn($this->id);
        $this->backupMock->expects($this->once())
            ->method('delete')
            ->willThrowException($e);
        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with($e, __('Cannot delete backup'))
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->deleteAction->execute());
    }
}
