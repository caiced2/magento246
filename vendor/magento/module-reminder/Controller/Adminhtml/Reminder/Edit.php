<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Controller\Adminhtml\Reminder;

class Edit extends \Magento\Reminder\Controller\Adminhtml\Reminder
{
    /**
     * Init active menu and set breadcrumb
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_Reminder::promo_reminder'
        )->_addBreadcrumb(
            __('Reminder Rules'),
            __('Reminder Rules')
        );
        return $this;
    }

    /**
     * Edit reminder rule
     *
     * @return void
     */
    public function execute()
    {
        try {
            $model = $this->_initRule();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('adminhtml/*/');
            return;
        }

        // set entered data if was error when we do save
        $data = $this->_getSession()->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');

        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Email Reminders'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getName() : __('New Reminder Rule')
        );

        $this->_view->getLayout()->getBlock(
            'adminhtml_reminder_edit'
        )->setData(
            'form_action_url',
            $this->getUrl('adminhtml/*/save')
        );

        $caption = $model->getId() ? __('Edit Rule') : __('New Reminder Rule');
        $this->_addBreadcrumb($caption, $caption);
        $this->_view->renderLayout();
    }
}
