<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScheduledImportExport\Test\Unit\Controller\Adminhtml\Scheduled\Operation;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Console\Request;
use Magento\Framework\App\Console\Response;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\Manager;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation\Save;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use Magento\ScheduledImportExport\Model\Scheduled\OperationFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /** @var Save */
    protected $save;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /**
     * @var Context
     */
    protected $context;

    /** @var Registry|MockObject */
    protected $registry;

    /** @var Request|MockObject */
    protected $request;

    /** @var Response|MockObject */
    protected $response;

    /** @var Manager|MockObject */
    protected $messageManager;

    /** @var Session|MockObject */
    protected $session;

    /** @var ActionFlag|MockObject */
    protected $actionFlag;

    /** @var Data|MockObject */
    protected $backendHelper;

    /** @var  Operation|MockObject */
    protected $operation;

    /** @var \Magento\ScheduledImportExport\Helper\Data|MockObject */
    protected $scheduledHelper;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(Request::class)
            ->addMethods(['isPost', 'getPostValue'])
            ->onlyMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->createMock(Manager::class);
        $this->session = $this->createMock(Session::class);
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $this->backendHelper = $this->createMock(Data::class);
        $this->registry = $this->createMock(Registry::class);

        $operationFactory = $this->createPartialMock(
            OperationFactory::class,
            ['create']
        );
        $this->operation = $this->createMock(Operation::class);
        $operationFactory->expects($this->any())->method('create')->willReturn($this->operation);
        $this->scheduledHelper = $this->createMock(\Magento\ScheduledImportExport\Helper\Data::class);
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'request' => $this->request,
                'messageManager' => $this->messageManager,
                'session' => $this->session,
                'actionFlag' => $this->actionFlag,
                'helper' => $this->backendHelper,
                'resultFactory' => $this->resultFactoryMock
            ]
        );
        $this->save = $this->objectManagerHelper->getObject(
            Save::class,
            [
                'context' => $this->context,
                'coreRegistry' => $this->registry,
                'operationFactory' => $operationFactory,
                'dataHelper' => $this->scheduledHelper
            ]
        );
    }

    public function testExecuteError()
    {
        $this->request->expects($this->once())->method('isPost')->willReturn(true);
        $this->request->expects($this->once())->method('getPostValue')->willReturn([]);
        $this->messageManager->expects($this->once())->method('addError');
        $this->messageManager->expects($this->never())->method('addSuccess');
        $this->assertSame($this->resultRedirectMock, $this->save->execute());
    }

    public function testExecuteSuccess()
    {
        $this->request->expects($this->once())->method('isPost')->willReturn(true);
        $this->request->expects($this->once())->method('getPostValue')->willReturn([
            'id' => 1,
            'operation_type' => 'sometype',
            'start_time' => [12, 15]
        ]);
        $this->operation->expects($this->once())->method('setData');
        $this->operation->expects($this->once())->method('save');
        $this->messageManager->expects($this->never())->method('addError');
        $successMessage = 'Some sucess message';
        $this->scheduledHelper->expects($this->once())->method('getSuccessSaveMessage')->willReturn(
            $successMessage
        );
        $this->messageManager->expects($this->once())->method('addSuccess')->with($successMessage);
        $this->assertSame($this->resultRedirectMock, $this->save->execute());
    }
}
