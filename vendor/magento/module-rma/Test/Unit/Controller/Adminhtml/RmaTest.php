<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Title;
use Magento\Rma\Model\Item;
use Magento\Rma\Model\ResourceModel\Item\Collection;
use Magento\Rma\Model\Rma as RmaModel;
use Magento\Rma\Model\Rma\RmaDataMapper;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\Rma\Status\History;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class RmaTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class RmaTest extends TestCase
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var \Magento\Rma\Controller\Adminhtml\Rma
     */
    protected $action;

    /**
     * @var Http|MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\App\Response\Http|MockObject
     */
    protected $responseMock;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var ActionFlag|MockObject
     */
    protected $flagActionMock;

    /**
     * @var Collection|MockObject
     */
    protected $rmaCollectionMock;

    /**
     * @var Item|MockObject
     */
    protected $rmaItemMock;

    /**
     * @var RmaModel|MockObject
     */
    protected $rmaModelMock;

    /**
     * @var Order|MockObject
     */
    protected $orderMock;

    /**
     * @var Status|MockObject
     */
    protected $sourceStatusMock;

    /**
     * @var History|MockObject
     */
    protected $statusHistoryMock;

    /**
     * @var ViewInterface|MockObject
     */
    protected $viewMock;

    /**
     * @var Title|MockObject
     */
    protected $titleMock;

    /**
     * @var Form|MockObject
     */
    protected $formMock;

    /**
     * @var RmaDataMapper|MockObject
     */
    protected $rmaDataMapperMock;

    /**
     * @var SessionManagerInterface|MockObject
     */
    protected $sessionManager;

    /**
     * test setUp
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $contextMock = $this->createMock(Context::class);
        $backendHelperMock = $this->createMock(Data::class);
        $this->rmaDataMapperMock = $this->createMock(RmaDataMapper::class);
        $this->viewMock = $this->getMockForAbstractClass(ViewInterface::class);
        $this->titleMock = $this->createMock(Title::class);
        $this->formMock = $this->getMockBuilder(Form::class)
            ->addMethods(['hasNewAttributes'])
            ->onlyMethods(['toHtml'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->initMocks();
        $contextMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $contextMock->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->responseMock);
        $contextMock->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);
        $contextMock->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $contextMock->expects($this->once())
            ->method('getSession')
            ->willReturn($this->sessionMock);
        $contextMock->expects($this->once())
            ->method('getActionFlag')
            ->willReturn($this->flagActionMock);
        $contextMock->expects($this->once())
            ->method('getHelper')
            ->willReturn($backendHelperMock);
        $contextMock->expects($this->once())
            ->method('getView')
            ->willReturn($this->viewMock);
        $this->sessionManager = $this->getMockForAbstractClass(SessionManagerInterface::class);
        $arguments = $this->getConstructArguments();
        $arguments['context'] = $contextMock;
        $arguments['sessionManager'] = $this->sessionManager;

        $this->action = $objectManager->getObject(
            'Magento\\Rma\\Controller\\Adminhtml\\Rma\\' . $this->name,
            $arguments
        );
    }

    /**
     * @return array
     */
    protected function getConstructArguments()
    {
        return [
            'coreRegistry' => $this->coreRegistryMock,
            'rmaDataMapper' => $this->rmaDataMapperMock
        ];
    }

    protected function initMocks()
    {
        $this->coreRegistryMock = $this->createMock(Registry::class);
        $this->requestMock = $this->createMock(Http::class);
        $this->responseMock = $this->createPartialMock(\Magento\Framework\App\Response\Http::class, [
            'setBody',
            'representJson',
            'setRedirect',
            '__wakeup'
        ]);
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->flagActionMock = $this->createMock(ActionFlag::class);
        $this->rmaCollectionMock = $this->createMock(Collection::class);
        $this->rmaItemMock = $this->createMock(Item::class);
        $this->rmaModelMock = $this->createPartialMock(RmaModel::class, [
            'saveRma',
            'getId',
            'setStatus',
            'load',
            'canClose',
            'close',
            'save',
            '__wakeup'
        ]);
        $this->orderMock = $this->createMock(Order::class);
        $this->sourceStatusMock = $this->createMock(Status::class);
        $this->statusHistoryMock = $this->getMockBuilder(History::class)
            ->addMethods(['setRma'])
            ->onlyMethods(
                [
                    'setRmaEntityId',
                    'sendNewRmaEmail',
                    'saveComment',
                    'saveSystemComment',
                    'setComment',
                    'sendAuthorizeEmail',
                    'sendCommentEmail'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->willReturnMap(
                [
                    [Collection::class, [], $this->rmaCollectionMock],
                    [Item::class, [], $this->rmaItemMock],
                    [RmaModel::class, [], $this->rmaModelMock],
                    [Order::class, [], $this->orderMock],
                    [Status::class, [], $this->sourceStatusMock],
                    [History::class, [], $this->statusHistoryMock],
                    [SessionManagerInterface::class],
                    [],
                    $this->sessionManager
                ]
            );
    }

    protected function initRequestData($commentText = '', $visibleOnFront = true)
    {
        $rmaConfirmation = true;
        $post = [
            'items' => [],
            'rma_confirmation' => $rmaConfirmation,
            'comment' => [
                'comment' => $commentText,
                'is_visible_on_front' => $visibleOnFront,
            ],
        ];
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn(
                [
                    'items' => [],
                    'rma_confirmation' => $rmaConfirmation,
                    'comment' => [
                        'comment' => $commentText,
                        'is_visible_on_front' => $visibleOnFront,
                    ],
                ]
            );
        return $post;
    }
}
