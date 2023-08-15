<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Returns;

use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Class Returns
 */
class Returns extends \Magento\Rma\Controller\Returns implements HttpGetActionInterface
{
    /**
     * View RMA for Order
     *
     * @return false|null
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function execute()
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        $customerId = $this->_objectManager->get(\Magento\Customer\Model\Session::class)->getCustomerId();

        if (!$orderId || !$this->_isEnabledOnFront()) {
            $this->_forward('noroute');
            return false;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_objectManager->create(\Magento\Sales\Model\Order::class)->load($orderId);

        $availableStatuses = $this->_objectManager
            ->get(\Magento\Sales\Model\Order\Config::class)
            ->getVisibleOnFrontStatuses();
        if ($order->getId() && $order->getCustomerId() && $order->getCustomerId() == $customerId && in_array(
            $order->getStatus(),
            $availableStatuses,
            $strict = true
        )
        ) {
            $this->_coreRegistry->register('current_order', $order);
        } else {
            $this->_redirect('*/*/history');
            return;
        }

        $this->_view->loadLayout();
        $layout = $this->_view->getLayout();

        if ($navigationBlock = $layout->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('sales/order/history');
        }
        $this->_view->renderLayout();
    }
}
