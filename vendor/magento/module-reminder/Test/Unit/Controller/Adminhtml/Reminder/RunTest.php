<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Controller\Adminhtml\Reminder;

use Magento\Reminder\Controller\Adminhtml\Reminder\Run;
use Magento\Reminder\Test\Unit\Controller\Adminhtml\AbstractReminder;
use Psr\Log\LoggerInterface;

class RunTest extends AbstractReminder
{
    /**
     * @var Run
     */
    protected $runController;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->runController = new Run(
            $this->context,
            $this->coreRegistry,
            $this->ruleFactory,
            $this->conditionFactory,
            $this->dataFilter,
            $this->timeZoneResolver
        );
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $this->initRule();

        $this->rule
            ->method('sendReminderEmails')
            ->willReturn(true);
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('You matched the reminder rule.'))
            ->willReturn(true);
        $this->request->expects($this->any())->method('getParam')->with('id', 0)->willReturn(0);
        $this->redirect('adminhtml/*/edit', ['id' => 0, 'active_tab' => 'matched_customers']);

        $this->runController->execute();
    }

    /**
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->initRuleWithException();

        $this->messageManager->expects($this->once())
            ->method('addError')->with(__('Please correct the reminder rule you requested.'));
        $this->request
            ->method('getParam')
            ->willReturnOnConsecutiveCalls(0);
        $this->redirect('adminhtml/*/edit', ['id' => 0, 'active_tab' => 'matched_customers']);

        $this->runController->execute();
    }

    /**
     * @return void
     */
    public function testExecuteWithException2(): void
    {
        $this->initRuleWithException();

        $this->ruleFactory->expects($this->once())
            ->method('create')->willThrowException(new \Exception('Exception massage'));
        $this->messageManager->expects($this->once())
            ->method('addException');
        $this->objectManagerMock->expects($this->once())
            ->method('get')->with(LoggerInterface::class)->willReturn($this->logger);
        $this->request
            ->method('getParam')
            ->willReturnOnConsecutiveCalls(0);
        $this->redirect('adminhtml/*/edit', ['id' => 0, 'active_tab' => 'matched_customers']);

        $this->runController->execute();
    }
}
