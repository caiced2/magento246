<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Index;

class AddSelect extends \Magento\GiftRegistry\Controller\Index
{
    /**
     * Add select gift registry action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $block = $this->_view->getLayout()->getBlock('giftregistry_addselect');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Create Gift Registry'));
        $this->_view->renderLayout();
    }
}
