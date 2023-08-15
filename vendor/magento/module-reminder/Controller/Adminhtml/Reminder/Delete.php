<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Controller\Adminhtml\Reminder;

class Delete extends \Magento\Reminder\Controller\Adminhtml\Reminder
{
    /**
     * Delete reminder rule
     *
     * @return void
     */
    public function execute()
    {
        try {
            $model = $this->_initRule();
            $model->delete();
            $this->messageManager->addSuccess(__('You deleted the reminder rule.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('adminhtml/*/edit', ['id' => $model->getId()]);
            return;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t delete the reminder rule right now.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }
        $this->_redirect('adminhtml/*/');
    }
}
