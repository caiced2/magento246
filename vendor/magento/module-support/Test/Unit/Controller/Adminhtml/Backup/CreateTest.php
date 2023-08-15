<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Backup;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Controller\Adminhtml\Backup\Create;
use Magento\Support\Model\Backup;
use Magento\Support\Model\ResourceModel\Backup\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Collection|MockObject
     */
    protected $backupCollectionMock;

    /**
     * @var Backup|MockObject
     */
    protected $backupModelMock;

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
     * @var Create
     */
    protected $createAction;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->backupCollectionMock = $this->getMockBuilder(
            Collection::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->backupModelMock = $this->createMock(Backup::class);
        $this->redirectMock = $this->createMock(Redirect::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->redirectMock);

        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'messageManager' => $this->messageManagerMock,
                'resultFactory' => $this->resultFactoryMock
            ]
        );
        $this->createAction = $this->objectManagerHelper->getObject(
            Create::class,
            [
                'context' => $this->context,
                'backupModel' => $this->backupModelMock,
                'backupCollection' => $this->backupCollectionMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $this->setBackupCollectionMock();

        $this->backupModelMock->expects($this->once())
            ->method('run');
        $this->backupModelMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('The backup has been saved.'))
            ->willReturnSelf();

        $this->runTestExecute();
    }

    /**
     * @return void
     */
    public function testExecuteWithStateException()
    {
        $this->setBackupCollectionMock(1);

        $this->backupModelMock->expects($this->never())->method('run');
        $this->backupModelMock->expects($this->never())->method('save');
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('All processes should be completed.'))
            ->willReturnSelf();

        $this->runTestExecute();
    }

    /**
     * @return void
     */
    public function testExecuteWithNotFoundException()
    {
        $e = new NotFoundException(
            __('Cannot save backup. The reason is: Utility lsof not found')
        );
        $this->backupCollectionMock->expects($this->once())
            ->method('addProcessingStatusFilter')
            ->willThrowException($e);
        $this->backupModelMock->expects($this->never())->method('run');
        $this->backupModelMock->expects($this->never())->method('save');
        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with($e)
            ->willReturnSelf();

        $this->runTestExecute();
    }

    /**
     * @return void
     */
    public function testExecuteWithException()
    {
        $e = new \Exception();
        $this->backupCollectionMock->expects($this->once())
            ->method('addProcessingStatusFilter')
            ->willThrowException($e);
        $this->backupModelMock->expects($this->never())->method('run');
        $this->backupModelMock->expects($this->never())->method('save');
        $this->messageManagerMock->expects($this->once())
            ->method('addException')
            ->with($e, __('An error occurred while saving backup'))
            ->willReturnSelf();

        $this->runTestExecute();
    }

    /**
     * @return void
     */
    protected function runTestExecute()
    {
        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*/index')
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->createAction->execute());
    }

    /**
     * @param int $returnedValue
     * @return void
     */
    protected function setBackupCollectionMock($returnedValue = 0)
    {
        $this->backupCollectionMock->expects($this->once())
            ->method('addProcessingStatusFilter')
            ->willReturnSelf();
        $this->backupCollectionMock->expects($this->once())
            ->method('count')
            ->willReturn($returnedValue);
    }
}
