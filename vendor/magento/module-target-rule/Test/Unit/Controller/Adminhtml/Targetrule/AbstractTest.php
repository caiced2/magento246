<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\TargetRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractTest extends TestCase
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
     * @var Date|MockObject
     */
    protected $dateMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

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
     * @var Data|MockObject
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
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var LayoutInterface|MockObject
     */
    protected $layoutMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $breadcrumbsBlockMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $menuBlockMock;

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
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->authorizationMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->authMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->setMethods(['setStatusHeader', 'setRedirect', 'representJson', 'setBody', 'sendResponse'])
            ->getMockForAbstractClass();
        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['getEventData', 'setIsUrlNotice', 'getFormData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionFlagMock = $this->getMockBuilder(ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backendHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['isDispatched', 'initForward', 'setDispatched', 'isForwarded'])
            ->getMockForAbstractClass();

        $this->viewMock = $this->getMockBuilder(ViewInterface::class)
            ->getMock();

        $this->breadcrumbsBlockMock = $this->getMockBuilder(BlockInterface::class)
            ->setMethods(['addLink', 'toHtml'])
            ->getMockForAbstractClass();

        $this->menuBlockMock = $this->getMockBuilder(BlockInterface::class)
            ->setMethods(['setActive', 'getMenuModel', 'toHtml'])
            ->getMockForAbstractClass();

        $this->switcherBlockMock = $this->getMockBuilder(BlockInterface::class)
            ->setMethods(['setDefaultStoreName', 'toHtml', 'setSwitchUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->getMock();
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

        $this->viewMock
            ->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->getMock();
        $this->dateMock = $this->getMockBuilder(Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->backendHelperMock
            ->expects($this->any())
            ->method('getUrl')
            ->willReturnArgument(0);
    }

    /**
     * @param string $className
     * @param string $prefix
     * @return MockObject
     */
    protected function getMockForConditionsHtmlAction($className, $prefix)
    {
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $conditionMock = $this->getMockBuilder($className)
            ->setMethods(
                ['setId', 'setType', 'setRule', 'setPrefix', 'setAttribute', 'setJsFormObject', 'asHtmlRecursive']
            )
            ->disableOriginalConstructor()
            ->getMock();
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setId')
            ->with(123)
            ->willReturnSelf();
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setType')
            ->with('foo')
            ->willReturnSelf();
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setRule')
            ->with($ruleMock)
            ->willReturnSelf();
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setAttribute')
            ->with('bar');
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setPrefix')
            ->with($prefix)
            ->willReturnSelf();

        $this->objectManagerMock
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['foo', []],
                [Rule::class, []]
            )
            ->willReturnOnConsecutiveCalls(
                $conditionMock,
                $ruleMock
            );

        return $conditionMock;
    }
}
