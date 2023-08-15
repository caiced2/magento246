<?php
/**
 * Quote items grid ajax callback
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

class Cart extends \Magento\AdvancedCheckout\Controller\Adminhtml\Index
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $this->_initData();
            if ($this->_redirectFlag) {
                return;
            }
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        } catch (\Exception $e) {
            $this->_processException($e);
        }
    }
}
