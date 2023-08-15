<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\Reminder\Test\Unit\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Item;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Message\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use Magento\Reminder\Model\Rule;
use Magento\Reminder\Model\Rule\ConditionFactory;
use Magento\Rule\Model\Condition\Combine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractReminder
 * @package Magento\Reminder\Test\Unit\Controller\Adminhtml\Reminder
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractReminder extends TestCase
{
    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var Session|MockObject
     */
    protected $session;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $response;

    /**
     * @var RequestInterface|MockObject
     */
    protected $request;

    /**
     * @var Manager|MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\Reminder\Model\RuleFactory|MockObject
     */
    protected $ruleFactory;

    /**
     * @var Rule|MockObject
     */
    protected $rule;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistry;

    /**
     * @var Data|MockObject
     */
    protected $backendHelper;

    /**
     * @var DateTime|MockObject
     */
    protected $dataFilter;

    /**
     * @var ConditionFactory|MockObject
     */
    protected $conditionFactory;

    /**
     * @var ViewInterface|MockObject
     */
    protected $view;

    /**
     * @var LayoutInterface|MockObject
     */
    protected $layout;

    /**
     * @var BlockInterface|MockObject
     */
    protected $block;

    /**
     * @var Menu|MockObject
     */
    protected $menuModel;

    /**
     * @var Page|MockObject
     */
    protected $page;

    /**
     * @var Config|MockObject
     */
    protected $config;

    /**
     * @var Title|MockObject
     */
    protected $titleMock;

    /**
     * @var Item|MockObject
     */
    protected $item;

    /**
     * @var ConditionFactory|MockObject
     */
    protected $condition;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var ActionFlag|MockObject
     */
    protected $actionFlag;

    /**
     * @var RedirectFactory|MockObject
     */
    protected $resultRedirectFactory;

    /**
     * @var TimezoneInterface
     */
    protected $timeZoneResolver;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->titleMock = $this->createMock(Title::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class, [], '', false);
        $this->actionFlag = $this->createMock(ActionFlag::class);

        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(['setRedirect', 'setBody'])
            ->onlyMethods(['sendResponse'])
            ->getMockForAbstractClass();
        $this->request = $this->createMock(Http::class);
        $this->messageManager = $this->createMock(Manager::class);

        $this->resultRedirectFactory = $this->createPartialMock(
            RedirectFactory::class,
            ['create']
        );

        $this->session = $this->getMockBuilder(Session::class)
            ->addMethods(['setIsUrlNotice', 'setPageData', 'getPageData', 'setFormData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFilter = $this->createMock(DateTime::class);
        $this->conditionFactory = $this->createMock(ConditionFactory::class);
        $this->ruleFactory = $this->createPartialMock(\Magento\Reminder\Model\RuleFactory::class, ['create']);

        $this->rule = $this->getMockBuilder(Rule::class)
            ->addMethods(['getName', 'convertConfigTimeToUtc'])
            ->onlyMethods(
                [
                    'getData',
                    'getId',
                    'validateData',
                    'save',
                    'delete',
                    'load',
                    'setData',
                    'getConditions',
                    'addData',
                    'sendReminderEmails',
                    'loadPost'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->backendHelper = $this->createMock(Data::class);
        $this->coreRegistry = $this->createMock(Registry::class);

        $this->view = $this->getMockForAbstractClass(ViewInterface::class, [], '', false);

        $this->layout = $this->getMockForAbstractClass(LayoutInterface::class, [], '', false);
        $this->block = $this->getMockBuilder(BlockInterface::class)
            ->addMethods(['setActive', 'getMenuModel', 'addLink', 'setData'])
            ->onlyMethods(['toHtml'])
            ->getMockForAbstractClass();
        $this->condition = $this->createMock(Combine::class);
        $this->menuModel = $this->createMock(Menu::class);
        $this->page = $this->createMock(Page::class);
        $this->config = $this->createMock(Config::class);
        $this->item = $this->createMock(Item::class);

        $this->context = $this->createMock(Context::class);
        $this->context->expects($this->once())->method('getRequest')->willReturn($this->request);
        $this->context->expects($this->once())->method('getResponse')->willReturn($this->response);
        $this->context->expects($this->once())->method('getObjectManager')->willReturn($this->objectManagerMock);
        $this->context->expects($this->once())->method('getView')->willReturn($this->view);
        $this->context->expects($this->once())->method('getSession')->willReturn($this->session);
        $this->context->expects($this->once())->method('getResponse')->willReturn($this->response);
        $this->context->expects($this->once())->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->expects($this->once())->method('getHelper')->willReturn($this->backendHelper);
        $this->context->expects($this->once())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactory);
        $this->context->expects($this->once())->method('getActionFlag')->willReturn($this->actionFlag);

        $this->timeZoneResolver = $this->getMockForAbstractClass(TimezoneInterface::class);
    }

    /**
     * @return void
     */
    protected function initRuleWithException(): void
    {
        $this->request
            ->method('getParam')
            ->willReturnOnConsecutiveCalls(1);
        $this->ruleFactory->expects($this->once())->method('create')->willReturn($this->rule);
        $this->rule->expects($this->any())->method('getId')->willReturn(null);

        $this->coreRegistry->expects($this->never())
            ->method('register')->with('current_reminder_rule', $this->rule)->willReturn(1);
    }

    /**
     * @return void
     */
    protected function initRule(): void
    {
        $this->request
            ->method('getParam')
            ->willReturnOnConsecutiveCalls(1);
        $this->ruleFactory->expects($this->once())->method('create')->willReturn($this->rule);
        $this->rule->expects($this->any())->method('getId')->willReturn(1);
        $this->rule->expects($this->any())->method('load')->willReturnSelf();
        $this->coreRegistry->expects($this->any())
            ->method('register')->with('current_reminder_rule', $this->rule);
    }

    /**
     * @return void
     */
    protected function initRuleWithDate(): void
    {
        $this->request
            ->method('getParam')
            ->willReturn(1);
        $this->ruleFactory->expects($this->once())->method('create')->willReturn($this->rule);
        $this->rule->expects($this->atLeastOnce())->method('getId')->willReturn(1);

        $getDataMap = [
            ['from_date', null, '2015-12-19 00:00:00'],
            ['to_date', null, '2015-12-21 00:00:00']
        ];

        $this->rule->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnMap($getDataMap);

        $dateFormatMap = [
            [
                '2015-12-19 00:00:00',
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT,
                null,
                null,
                null,
                '2015-12-19 08:00:00'
            ],
            [
                '2015-12-21 00:00:00',
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT,
                null,
                null,
                null,
                '2015-12-21 08:00:00']
        ];

        $this->timeZoneResolver->expects($this->atLeastOnce())
            ->method('formatDateTime')
            ->willReturnMap($dateFormatMap);
        $this->rule->expects($this->atLeastOnce())
            ->method('setData')
            ->willReturn($this->returnSelf());
        $this->coreRegistry->expects($this->any())
            ->method('register')
            ->with('current_reminder_rule', $this->rule);
    }

    /**
     * @param string $path
     * @param array $args
     *
     * @return void
     */
    protected function redirect(string $path, array $args = []): void
    {
        $this->actionFlag->expects($this->any())->method('get');
        $this->session->expects($this->any())->method('setIsUrlNotice');
        $this->response->expects($this->once())->method('setRedirect');
        $this->backendHelper->expects($this->once())->method('getUrl')->with($path, $args);
    }
}
