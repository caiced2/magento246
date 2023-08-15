<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Controller\Adminhtml\Reminder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Reminder\Controller\Adminhtml\Reminder\Save;
use Magento\Reminder\Test\Unit\Controller\Adminhtml\AbstractReminder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SaveTest extends AbstractReminder
{
    /**
     * @var Save|MockObject
     */
    protected $saveController;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->saveController = new Save(
            $this->context,
            $this->coreRegistry,
            $this->ruleFactory,
            $this->conditionFactory,
            $this->dataFilter,
            $this->timeZoneResolver
        );
    }

    /**
     * Test with empty data variable
     *
     * @return void
     */
    public function testExecuteWithoutData(): void
    {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn([]);

        $this->redirect('adminhtml/*/');

        $this->saveController->execute();
    }

    /**
     * Test with set data variable but without date.
     *
     * @param array $params
     *
     * @return void
     * @dataProvider executeDataProviderWithoutDate()
     */
    public function testExecuteWithDataWithoutDate(array $params): void
    {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($params['post_data']);

        $this->request->expects($this->any())->method('getParam')->willReturnMap(
            [
                ['back', false, $params['redirectBack']],
                ['rule_id', null, $params['ruleId']]
            ]
        );
        $this->initRule();
        $model = $this->rule;

        $model->expects($this->exactly($params['validateData']))
            ->method('validateData')
            ->willReturn($params['validateResult']);

        $model->expects($this->once())->method('save')->willReturn(1);
        $model->expects($this->never())->method('convertConfigTimeToUtc');

        $this->session->expects($this->any())
            ->method('setPageData')
            ->willReturn(1);

        if ($params['redirectBack']) {
            $this->redirect('adminhtml/*/edit', ['id' => 1, '_current' => true]);
        }
        $this->messageManager->expects($this->exactly($params['addSuccess']))
            ->method('addSuccess')->with(__('You saved the reminder rule.'))->willReturn(true);
        $this->messageManager->expects($this->exactly($params['addError']))->method('addError')->willReturn(true);

        $this->saveController->execute();
    }

    /**
     * Test with set data variable and with date.
     *
     * @param array $params
     *
     * @return void
     * @dataProvider executeDataProviderWithDate()
     */
    public function testExecuteWithDataWithDate(array $params): void
    {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($params['post_data']);

        $this->request->expects($this->any())->method('getParam')->willReturnMap(
            [
                ['back', false, $params['redirectBack']],
                ['rule_id', null, $params['ruleId']]
            ]
        );
        $this->initRule();
        $model = $this->rule;

        $model->expects($this->exactly($params['validateData']))
            ->method('validateData')->willReturn($params['validateResult']);

        $this->session->expects($this->any())
            ->method('setPageData')->with(false)->willReturn(1);
        $model->expects($this->once())->method('save')->willReturn(1);
        $this->dataFilter->expects($this->any())->method('filter')->willReturn('2015-12-19 05:49:00');
        $this->timeZoneResolver
            ->expects($this->exactly(2))->method('convertConfigTimeToUtc')
            ->willReturn('2015-12-19 05:49:00');

        if ($params['redirectBack']) {
            $this->redirect('adminhtml/*/edit', ['id' => 1, '_current' => true]);
        }
        $this->messageManager->expects($this->exactly($params['addSuccess']))
            ->method('addSuccess')->with(__('You saved the reminder rule.'))->willReturn(true);
        $this->messageManager->expects($this->exactly($params['addError']))->method('addError')->willReturn(true);

        $this->saveController->execute();
    }

    /**
     * @param array $params
     *
     * @return void
     * @dataProvider postDataProvider()
     */
    public function testExecuteValidationError(array $params): void
    {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($params);

        $this->request->expects($this->any())->method('getParam')->willReturnMap(
            [
                ['back', false, false],
                ['rule_id', null, 1]
            ]
        );
        $this->initRule();
        $model = $this->rule;

        $model->expects($this->once())
            ->method('validateData')
            ->willReturn([__('Validate error 1'), __('Validate error 2')]);

        $model->expects($this->exactly(2))->method('getId')->willReturn(1);
        $this->messageManager->expects($this->exactly(2))->method('addError')->willReturn(true);
        $this->session->expects($this->any())
            ->method('setFormData')->willReturn(1);
        $this->redirect('adminhtml/*/edit', ['id' => $model->getId()]);

        $this->saveController->execute();
    }

    /**
     * @param array $params
     *
     * @return void
     * @dataProvider postDataProvider()
     */
    public function testExecuteWithException(array $params): void
    {
        $this->markTestSkipped('Takes too long.');
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($params);

        $this->request->expects($this->any())->method('getParam')->willReturnMap(
            [
                ['back', false, false],
                ['rule_id', null, 1]
            ]
        );
        $this->initRule();
        $model = $this->rule;

        $model->expects($this->once())
            ->method('validateData')
            ->willReturn(true);

        $model->expects($this->once())->method('loadPost')->willReturn(1);
        $this->session->expects($this->never())
            ->method('setPageData')->willReturn(1);

        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with(__('We could not save the reminder rule.'))->willReturn(true);

        $exception = new \Exception();
        $model->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($this->logger);

        $this->logger->expects($this->once())->method('critical')->with($exception)->willReturn(0);
        $this->redirect('adminhtml/*/');

        $this->saveController->execute();
    }

    /**
     * @param array $params
     *
     * @return void
     * @dataProvider postDataProvider()
     */
    public function testExecuteWithLocalizedException(array $params): void
    {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($params);

        $this->request->expects($this->any())->method('getParam')->willReturnMap(
            [
                ['back', false, false],
                ['rule_id', null, 1]
            ]
        );
        $this->initRule();
        $model = $this->rule;

        $model->expects($this->once())
            ->method('validateData')->willReturn(true);

        $model->expects($this->once())->method('loadPost')->willReturn(1);
        $this->session->expects($this->any())
            ->method('setPageData')->with($params)->willReturn(1);

        $exception = new LocalizedException(__('We could not save the reminder rule.'));
        $model->expects($this->never())
            ->method('save')->willThrowException($exception);

        $this->messageManager->expects($this->once())
            ->method('addError')->with($exception->getMessage())->willReturn(true);

        $model->expects($this->never())->method('getId')->willReturn(1);
        $this->redirect('adminhtml/*/', []);

        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->onlyMethods(['critical'])
            ->getMockForAbstractClass();
        $logger->expects($this->once())->method('critical')->willReturnSelf();
        $this->objectManagerMock->expects($this->once())->method('get')->willReturn($logger);
        $this->saveController->execute();
    }

    /**
     * Post data provider.
     *
     * @return array
     */
    public function postDataProvider(): array
    {
        return [
            'post data' => [[
                'param1' => 1,
                'param2' => 2,
                'rule' => [
                    'conditions' => 'yes',
                    'actions' => 'action'
                ],
                'from_date' => '',
                'to_date' => ''
            ]]
        ];
    }

    /**
     * Data provider for test.
     *
     * @return array
     */
    public function executeDataProviderWithoutDate(): array
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
                'addSuccess' => 1,
                'addError' => 0,
                'addException' => 0,
                'addException2' => 0,
                'post_data' => [
                    'param1' => 1,
                    'param2' => 2,
                    'rule' => [
                        'conditions' => 'yes',
                        'actions' => 'action'
                    ],
                    'from_date' => '',
                    'to_date' => ''
                ]
            ]],
            'case2' => [[
                'redirectBack' => true,
                'ruleId' => 1,
                'getId' => [2, 1],
                'modelLoad' => 1,
                'setFormData' => 0,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => true,
                'addSuccess' => 1,
                'addError' => 0,
                'addException' => 0,
                'addException2' => 0,
                'post_data' => [
                    'param1' => 1,
                    'param2' => 2,
                    'rule' => [
                        'conditions' => 'yes',
                        'actions' => 'action'
                    ],
                    'from_date' => '',
                    'to_date' => ''
                ]
            ]]
        ];
    }

    /**
     * Data provider for test with date.
     *
     * @return array
     */
    public function executeDataProviderWithDate(): array
    {
        return [
            [[
                'redirectBack' => false,
                'ruleId' => 1,
                'getId' => [1, 1],
                'modelLoad' => 1,
                'setFormData' => 0,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => true,
                'addSuccess' => 1,
                'addError' => 0,
                'addException' => 0,
                'addException2' => 0,
                'post_data' => [
                    'param1' => 1,
                    'param2' => 2,
                    'rule' => [
                        'conditions' => 'yes',
                        'actions' => 'action'
                    ],
                    'from_date' => '2015-12-19 21:49:00',
                    'to_date' => '2015-12-19 21:49:00'
                ]
            ]],
        ];
    }
}
