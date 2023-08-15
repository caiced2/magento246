<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class NewAction extends \Magento\Rma\Controller\Adminhtml\Rma implements HttpGetActionInterface
{
    /**
     * Create new RMA
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $customerId = $this->getRequest()->getParam('customer_id');
            $this->_redirect('adminhtml/*/chooseorder', ['customer_id' => $customerId]);
        } else {
            try {
                $this->_initCreateModel();
                $this->_initModel();
                if (!$this->_objectManager->get(\Magento\Rma\Helper\Data::class)->canCreateRma($orderId, true)) {
                    $this->messageManager->addError(__('There are no applicable items for return in this order.'));
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('adminhtml/*/');
                return;
            }

            $this->_initAction();
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('New Return'));
            $this->_view->renderLayout();
        }
    }
}
