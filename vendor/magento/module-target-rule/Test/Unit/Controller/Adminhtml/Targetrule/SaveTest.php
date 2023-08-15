<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Message\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Indexer\Controller\Adminhtml\Indexer\ListAction;
use Magento\TargetRule\Controller\Adminhtml\Targetrule\Save;
use Magento\TargetRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{

    /**
     * @var ListAction
     */
    protected $object;

    /**
     * @var Context
     */
    protected $contextMock;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var Date|MockObject
     */
    protected $dateFilter;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManagerMock;

    /**
     * @var Rule|MockObject
     */
    protected $model;

    /**
     * @var Http
     */
    protected $response;

    /**
     * @var \Magento\Framework\App\Request\Http|MockObject
     */
    protected $request;

    /**
     * @var ActionFlag
     */
    protected $actionFlag;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $helper;

    /**
     * @var Manager
     */
    protected $messageManager;

    /**
     * Set up test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createPartialMock(Context::class, [
            'getAuthorization',
            'getSession',
            'getActionFlag',
            'getAuth',
            'getView',
            'getHelper',
            'getBackendUrl',
            'getFormKeyValidator',
            'getLocaleResolver',
            'getCanUseBaseUrl',
            'getRequest',
            'getResponse',
            'getObjectManager',
            'getMessageManager'
        ]);

        $this->session = $this->getMockBuilder(Session::class)
            ->addMethods(['setIsUrlNotice', 'setFormData', 'setPageData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionFlag = $this->createPartialMock(ActionFlag::class, ['get']);

        $this->response = $this->createMock(Http::class);
        $this->helper = $this->createPartialMock(Data::class, ['getUrl']);
        $this->contextMock->expects($this->any())->method('getResponse')->willReturn($this->response);
        $this->contextMock->expects($this->any())->method('getHelper')->willReturn($this->helper);
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->contextMock->expects($this->any())->method('getObjectManager')->willReturn($this->objectManagerMock);
        $this->contextMock->expects($this->any())->method('getSession')->willReturn($this->session);
        $this->contextMock->expects($this->any())->method('getActionFlag')->willReturn($this->actionFlag);
        $this->coreRegistry = $this->createMock(Registry::class);
        $this->session->expects($this->any())->method('setIsUrlNotice')->willReturn(false);
        $this->actionFlag->expects($this->any())->method('get')->with('')->willReturn(false);
        $this->dateFilter = $this->createMock(Date::class);

        $this->messageManager = $this->createPartialMock(
            Manager::class,
            ['addSuccess', 'addError', 'addException']
        );

        $this->contextMock->expects($this->any())->method('getMessageManager')->willReturn($this->messageManager);

        $this->request = $this->createPartialMock(
            \Magento\Framework\App\Request\Http::class,
            ['getParam', 'isPost', 'getPostValue']
        );

        $this->model = $this->createPartialMock(
            Rule::class,
            ['validateData', 'getId', 'load', 'save', 'loadPost']
        );

        $this->contextMock->expects($this->any())->method('getRequest')->willReturn($this->request);
        $this->request->expects($this->any())->method('isPost')->willReturn(true);

        $this->objectManagerMock->expects($this->any())->method('create')->willReturn($this->model);
        $this->object = new Save(
            $this->contextMock,
            $this->coreRegistry,
            $this->dateFilter
        );
    }

    /**
     * Test with empty data variable
     *
     * @return void
     */
    public function testExecuteWithoutData()
    {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn([]);

        $route = 'URL';
        $this->helper->expects($this->any())->method('getUrl')->willReturn($route);

        $this->object->execute();
    }

    /**
     * Test with set data variable
     *
     * @param array $params
     * @dataProvider executeDataProvider()
     * @return void
     */
    public function testExecuteWithData($params)
    {
        $data = [
            'param1' => 1,
            'param2' => 2,
            'rule' => [
                'conditions' => 'yes',
                'actions' => 'action'
            ],
            'from_date' => $params['date']['from_date'],
            'to_date' => $params['date']['to_date']
        ];

        $this->assertsForDates($params['date']);

        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($data);

        $this->request->expects($this->any())->method('getParam')->willReturnMap(
            [
                ['back', false, $params['redirectBack']],
                ['rule_id', null, $params['ruleId']]
            ]
        );

        $this->model->expects($this->exactly($params['validateData']))
            ->method('validateData')->willReturn($params['validateResult']);
        $this->model->expects($this->exactly($params['modelLoad']))->method('load')->willReturn(true);
        $this->model->expects($this->exactly($params['getId'][0]))->method('getId')->willReturn($params['getId'][1]);
        $this->model->expects($this->exactly($params['modelLoadPostSave']))->method('loadPost')->willReturn(1);
        $this->model->expects($this->exactly($params['modelLoadPostSave']))->method('save')->willReturn(1);

        if ($params['addException'] != 0) {
            $this->model->expects($this->exactly($params['addException']))
                ->method('save')->willThrowException(new \Exception());
            $this->session->expects($this->exactly($params['setPageData']))->method('setPageData')->willReturn(1);
        }

        $this->session->expects($this->exactly($params['setFormData']))->method('setFormData')->willReturn(1);

        $route = 'URL';
        $this->helper->expects($this->any())->method('getUrl')->willReturn($route);

        $this->messageManager->expects($this->exactly($params['addSuccess']))->method('addSuccess')->willReturn(true);
        $this->messageManager->expects($this->exactly($params['addError']))->method('addError')->willReturn(true);
        $this->messageManager->expects($this->exactly($params['addException']))->method('addException')
            ->willReturn(true);

        $this->object->execute();
    }

    /**
     * Data provider for test
     */
    public function executeDataProvider()
    {
        return [
            'case1' => [[
                'redirectBack' => false,
                'ruleId' => 1,
                'getId' => [1, 1],
                'modelLoad' => 1,
                'setFormData' => 0,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => true,
                'modelLoadPostSave' => 1,
                'addSuccess' => 1,
                'addError' => 0,
                'addException' => 0,
                'date' => [
                    'from_date' => '2016-07-12',
                    'to_date' => '2016-08-12'
                ]
            ]],
            'case2' => [[
                'redirectBack' => false,
                'ruleId' => 1,
                'getId' => [1, 2], // expected times, mock return value
                'modelLoad' => 1,
                'setFormData' => 1,
                'setPageData' => 0,
                'validateData' => 0,
                'validateResult' => true,
                'modelLoadPostSave' => 0,
                'addSuccess' => 0,
                'addError' => 1,
                'addException' => 0,
                'date' => [
                    'from_date' => '2016-07-12',
                    'to_date' => ''
                ]
            ]],
            'case3' => [[
                'redirectBack' => false,
                'ruleId' => 1,
                'getId' => [1, 1],
                'modelLoad' => 1,
                'setFormData' => 1,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => [__('Validate error 1'), __('Validate error 2')],
                'modelLoadPostSave' => 0,
                'addSuccess' => 0,
                'addError' => 2,
                'addException' => 0,
                'date' => [
                    'from_date' => '',
                    'to_date' => '2016-07-12'
                ]
            ]],
            'case4' => [[
                'redirectBack' => false,
                'ruleId' => 1,
                'getId' => [1, 1],
                'modelLoad' => 1,
                'setFormData' => 1,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => true,
                'modelLoadPostSave' => 1,
                'addSuccess' => 0,
                'addError' => 1,
                'addException' => 1,
                'date' => [
                    'from_date' => '',
                    'to_date' => ''
                ]
            ]],
            'case5' => [[
                'redirectBack' => true,
                'ruleId' => 1,
                'getId' => [2, 1],
                'modelLoad' => 1,
                'setFormData' => 0,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => true,
                'modelLoadPostSave' => 1,
                'addSuccess' => 1,
                'addError' => 0,
                'addException' => 0,
                'date' => [
                    'from_date' => '',
                    'to_date' => ''
                ]
            ]]
        ];
    }

    private function assertsForDates(array $dates)
    {
        $datesCount = count($dates);
        if (!$dates['from_date']) {
            $datesCount--;
        }
        if (!$dates['to_date']) {
            $datesCount--;
        }
        $this->dateFilter
            ->expects(static::exactly($datesCount))
            ->method('filter')
            ->willReturn(static::returnArgument(0));
    }
}
