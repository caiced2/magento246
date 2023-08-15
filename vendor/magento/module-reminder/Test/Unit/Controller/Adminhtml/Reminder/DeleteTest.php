<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Controller\Adminhtml\Reminder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Reminder\Controller\Adminhtml\Reminder\Delete;
use Magento\Reminder\Test\Unit\Controller\Adminhtml\AbstractReminder;
use Psr\Log\LoggerInterface;

class DeleteTest extends AbstractReminder
{
    /**
     * @var Delete
     */
    protected $deleteController;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteController = new Delete(
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
            ->method('delete')
            ->willReturn(true);
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('You deleted the reminder rule.'))
            ->willReturn(true);

        $this->redirect('adminhtml/*/', []);

        $this->deleteController->execute();
    }

    /**
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $this->initRule();
        $exception = new LocalizedException(
            __('Please correct the reminder rule you requested.')
        );
        $this->rule->expects($this->once())->method('delete')->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')->with(__('Please correct the reminder rule you requested.'));
        $this->redirect('adminhtml/*/edit', ['id' => 1]);

        $this->deleteController->execute();
    }

    /**
     * @return void
     */
    public function testExecuteWithException2(): void
    {
        $this->initRuleWithException();
        $exception = new \Exception('Exception message');
        $this->ruleFactory->expects($this->once())
            ->method('create')->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addError')->with(__('We can\'t delete the reminder rule right now.'));
        $this->objectManagerMock->expects($this->once())
            ->method('get')->with(LoggerInterface::class)->willReturn($this->logger);
        $this->logger->expects($this->once())->method('critical')->with($exception)->willReturn(0);
        $this->redirect('adminhtml/*/', []);

        $this->deleteController->execute();
    }
}
