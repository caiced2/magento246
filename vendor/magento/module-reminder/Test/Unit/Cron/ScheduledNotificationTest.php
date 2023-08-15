<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reminder\Cron\ScheduledNotification;
use Magento\Reminder\Helper\Data;
use Magento\Reminder\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScheduledNotificationTest extends TestCase
{
    /**
     * @var ScheduledNotification
     */
    private $model;

    /**
     * @var Data|MockObject
     */
    private $reminderData;

    /**
     * @var \Magento\Reminder\Model\RuleFactory|MockObject
     */
    private $ruleFactory;

    /**
     * @var Rule|MockObject
     */
    private $rule;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->reminderData = $this->getMockBuilder(Data::class)
            ->setMethods(['isEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->rule = $this->getMockBuilder(Rule::class)
            ->setMethods(['sendReminderEmails', '__wakeup', 'detachSalesRule'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ruleFactory = $this->getMockBuilder(\Magento\Reminder\Model\RuleFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleFactory->expects($this->any())->method('create')->willReturn($this->rule);

        $this->model = $helper->getObject(
            ScheduledNotification::class,
            ['reminderData' => $this->reminderData, 'ruleFactory' => $this->ruleFactory]
        );
    }

    /**
     * @return void
     */
    public function testScheduledNotification()
    {
        $this->reminderData->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->rule->expects($this->once())->method('sendReminderEmails');

        $this->model->execute();
    }

    /**
     * @return void
     */
    public function testScheduledNotificationDisabled()
    {
        $this->reminderData->expects($this->once())->method('isEnabled')->willReturn(false);

        $this->model->execute();
    }
}
