<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\Event;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth;
use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Session;
use Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\Edit;
use Magento\CatalogEvent\Helper\Data;
use Magento\CatalogEvent\Model\DateResolver;
use Magento\CatalogEvent\Model\Event;
use Magento\CatalogEvent\Model\EventFactory;
use Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\AbstractEventTest;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\DateTime;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Title;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditTest extends AbstractEventTest
{
    /**
     * @var Edit
     */
    protected $edit;

    /**
     * @var DateResolver|MockObject
     */
    protected $dateResolverMock;

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
        $this->dateResolverMock = $this->getMockBuilder(DateResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['convertDate'])
            ->getMock();

        $this->helperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

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

        $this->edit = new Edit(
            $this->contextMock,
            $this->registryMock,
            $this->eventFactoryMock,
            $this->dateTimeMock,
            $this->storeManagerMock
        );
    }

    /**
     * @param int $eventId
     * @param mixed $prepend
     * @param string $dateEnd
     * @param string $dateStart
     * @param string $modifiedDateEnd
     * @param string $modifiedDateStart
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($eventId, $prepend, $dateEnd, $dateStart, $modifiedDateEnd, $modifiedDateStart)
    {
        $eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['setStoreId', 'getId', 'getDateEnd', 'getDateStart', 'load', 'setDateEnd', 'setDateStart'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock
            ->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();
        $eventMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn($eventId);

        $this->eventFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($eventMock);

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['id', false, $eventId],
                    ['category_id', null, 999]
                ]
            );

        $this->objectManagerMock->expects($this->any())
            ->method('get')
            ->with(DateResolver::class)
            ->willReturn($this->dateResolverMock);

        $eventMock->expects($this->any())
            ->method('getDateEnd')
            ->willReturn($dateEnd);

        $eventMock->expects($this->any())
            ->method('getDateStart')
            ->willReturn($dateStart);
        $eventMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->dateResolverMock->expects($this->any())
            ->method('convertDate')
            ->will($this->onConsecutiveCalls($modifiedDateEnd, $modifiedDateStart));

        $this->sessionMock
            ->expects($this->any())
            ->method('getEventData')
            ->willReturn(['some data']);

        $titleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $titleMock
            ->expects($this->exactly(2))
            ->method('prepend')
            ->withConsecutive(
                [new Phrase('Events')],
                [$prepend]
            );

        $this->viewMock
            ->expects($this->any())
            ->method('getPage')
            ->willReturn(new DataObject(['config' => new DataObject(['title' => $titleMock])]));

        $this->switcherBlockMock
            ->expects($this->any())
            ->method('setDefaultStoreName')
            ->willReturnSelf();

        $this->edit->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [123, '#123', '12/3/15 12:00 AM', '12/2/15 12:00 AM', '12/3/15 10:00 AM', '12/2/15 10:00 AM'],
            [123, '#123', '12/3/15 12:00 AM', '12/2/15 12:00 AM', '12/3/15 10:00 AM', '12/2/15 10:00 AM'],
            [
                null,
                new Phrase('New Event'),
                '12/3/15 12:00 AM',
                '12/2/15 12:00 AM',
                '12/3/15 10:00 AM',
                '12/2/15 10:00 AM'
            ]
        ];
    }
}
