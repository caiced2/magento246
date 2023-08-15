<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Controller\Adminhtml\Reminder;

class Run extends \Magento\Reminder\Controller\Adminhtml\Reminder
{
    /**
     * Match reminder rule and send emails for matched customers
     *
     * @return void
     */
    public function execute()
    {
        try {
            $model = $this->_initRule();
            $model->sendReminderEmails();
            $this->messageManager->addSuccess(__('You matched the reminder rule.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Reminder rule matching error.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }
        $this->_redirect(
            'adminhtml/*/edit',
            ['id' => $this->getRequest()->getParam('id', 0), 'active_tab' => 'matched_customers']
        );
    }
}
