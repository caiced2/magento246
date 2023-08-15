<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reminder\Cron;

class ScheduledNotification
{
    /**
     * Reminder data
     *
     * @var \Magento\Reminder\Helper\Data
     */
    protected $_reminderData = null;

    /**
     * Remainder Rule Factory
     *
     * @var \Magento\Reminder\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * Constructor
     *
     * @param \Magento\Reminder\Helper\Data $reminderData
     * @param \Magento\Reminder\Model\RuleFactory $ruleFactory
     */
    public function __construct(
        \Magento\Reminder\Helper\Data $reminderData,
        \Magento\Reminder\Model\RuleFactory $ruleFactory
    ) {
        $this->_reminderData = $reminderData;
        $this->_ruleFactory = $ruleFactory;
    }

    /**
     * Send scheduled notifications
     *
     * @return void
     */
    public function execute()
    {
        if ($this->_reminderData->isEnabled()) {
            $this->_ruleFactory->create()->sendReminderEmails();
        }
    }
}
