<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Session;
use Magento\CatalogEvent\Helper\Data;
use Magento\CatalogEvent\Model\EventFactory;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\DateTime;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractEventTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var EventFactory|MockObject
     */
    protected $eventFactoryMock;

    /**
     * @var DateTime|MockObject
     */
    protected $dateTimeMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var Data|MockObject
     */
    protected $helperMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Auth|MockObject
     */
    protected $authMock;

    /**
     * @var AuthorizationInterface|MockObject
     */
    protected $authorizationMock;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $responseMock;

    /**
     * @var Session|MockObject
     */
    protected $sessionMock;

    /**
     * @var ActionFlag|MockObject
     */
    protected $actionFlagMock;

    /**
     * @var \Magento\Backend\Helper\Data|MockObject
     */
    protected $backendHelperMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ViewInterface|MockObject
     */
    protected $viewMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $breadcrumbsBlockMock;

    /**
     * @var LayoutInterface|MockObject
     */
    protected $layoutMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $menuBlockMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $switcherBlockMock;

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->helperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManagerMock
            ->expects($this->any())
            ->method('get')
            ->with(Data::class)
            ->willReturn($this->helperMock);

        $this->authorizationMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->authMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseMock = $this->getMockForAbstractClass(
            ResponseInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setStatusHeader', 'setRedirect', 'representJson']
        );

        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['getEventData', 'setIsUrlNotice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionFlagMock = $this->getMockBuilder(ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->backendHelperMock = $this->getMockBuilder(\Magento\Backend\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isDispatched', 'initForward', 'setDispatched', 'isForwarded']
        );

        $this->viewMock = $this->getMockForAbstractClass(
            ViewInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );

        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock
            ->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getAuth')
            ->willReturn($this->authMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getAuthorization')
            ->willReturn($this->authorizationMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getSession')
            ->willReturn($this->sessionMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getActionFlag')
            ->willReturn($this->actionFlagMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getHelper')
            ->willReturn($this->backendHelperMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getView')
            ->willReturn($this->viewMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->getMock();
        $this->eventFactoryMock = $this->getMockBuilder(EventFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();

        $this->breadcrumbsBlockMock = $this->getMockForAbstractClass(
            BlockInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['addLink']
        );

        $this->menuBlockMock = $this->getMockForAbstractClass(
            BlockInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setActive', 'getMenuModel']
        );

        $this->layoutMock = $this->getMockForAbstractClass(LayoutInterface::class);

        $this->viewMock
            ->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);

        $this->switcherBlockMock = $this->getMockBuilder(BlockInterface::class)
            ->setMethods(['setDefaultStoreName', 'toHtml', 'setSwitchUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->layoutMock
            ->expects($this->any())
            ->method('getBlock')
            ->willReturnMap(
                [
                    ['breadcrumbs', $this->breadcrumbsBlockMock],
                    ['menu', $this->menuBlockMock],
                    ['store_switcher', $this->switcherBlockMock]
                ]
            );

        $menuModelMock = $this->getMockBuilder(Menu::class)
            ->disableOriginalConstructor()
            ->getMock();
        $menuModelMock
            ->expects($this->any())
            ->method('getParentItems')
            ->willReturn([]);

        $this->menuBlockMock
            ->expects($this->any())
            ->method('getMenuModel')
            ->willReturn($menuModelMock);
    }
}
