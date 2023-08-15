<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Returns;

use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Controller class create. Renders returns creation page
 *
 * @package Magento\Rma\Controller\Returns
 */
class Create extends \Magento\Rma\Controller\Returns implements HttpGetActionInterface
{
    /**
     * Try to load valid collection of ordered items
     *
     * @param int $orderId
     * @return bool
     */
    protected function _loadOrderItems($orderId)
    {
        /** @var $rmaHelper \Magento\Rma\Helper\Data */
        $rmaHelper = $this->_objectManager->get(\Magento\Rma\Helper\Data::class);
        if ($rmaHelper->canCreateRma($orderId)) {
            return true;
        }

        $incrementId = $this->_coreRegistry->registry('current_order')->getIncrementId();
        $message = __('We can\'t create a return transaction for order #%1.', $incrementId);
        $this->messageManager->addError($message);
        $this->_redirect('sales/order/history');
        return false;
    }

    /**
     * Customer create new return
     *
     * @return void
     */
    public function execute()
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        if (empty($orderId)) {
            $this->_redirect('sales/order/history');
            return;
        }
        /** @var $order \Magento\Sales\Model\Order */
        $order = $this->_objectManager->create(\Magento\Sales\Model\Order::class)->load($orderId);

        if (!$this->_canViewOrder($order)) {
            $this->_redirect('sales/order/history');
            return;
        }

        $this->_coreRegistry->register('current_order', $order);

        if (!$this->_loadOrderItems($orderId)) {
            return;
        }

        $this->_view->loadLayout();

        $this->_view->getPage()->getConfig()->getTitle()->set(__('Create New Return'));
        if ($block = $this->_view->getLayout()->getBlock('customer.account.link.back')) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }
        $this->_view->renderLayout();
    }
}
