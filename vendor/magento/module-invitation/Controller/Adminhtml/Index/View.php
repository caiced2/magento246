<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Adminhtml\Index;

class View extends \Magento\Invitation\Controller\Adminhtml\Index
{
    /**
     * Invitation view action
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->_initInvitation();
            $this->_view->loadLayout();
            $this->_setActiveMenu('Magento_Invitation::customer_magento_invitation');
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Invitations'));
            $this->_view->renderLayout();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('invitations/*/');
        }
    }
}
