<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Index;

class Index extends \Magento\Invitation\Controller\Index
{
    /**
     * View invitation list in 'My Account' section
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->loadLayoutUpdates();
        if ($block = $this->_view->getLayout()->getBlock('invitations_list')) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }
        $this->_view->getPage()->getConfig()->getTitle()->set(__('My Invitations'));
        $this->_view->renderLayout();
    }
}
