<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

class RmaOrder extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Generate RMA grid for ajax request from order page
     *
     * @return void
     */
    public function execute()
    {
        $orderId = intval($this->getRequest()->getParam('order_id'));
        $this->getResponse()->setBody(
            $this->_view->getLayout()->createBlock(
                \Magento\Rma\Block\Adminhtml\Order\View\Tab\Rma::class
            )->setOrderId(
                $orderId
            )->toHtml()
        );
    }
}
