<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Rma\Controller\Adminhtml\Rma as RmaAction;

class AddProductGrid extends RmaAction implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * Generate RMA items grid for ajax request from selecting product grid during RMA creation
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $this->_initModel();
            $order = $this->_coreRegistry->registry('current_order');
            if (!$order) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid order'));
            }
            $this->_view->loadLayout();
            $response = $this->_view->getLayout()->getBlock('add_product_grid')->toHtml();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('We can\'t retrieve the product list right now.')];
        }
        if (is_array($response)) {
            $response = $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($response);
            $this->getResponse()->representJson($response);
        } else {
            $this->getResponse()->setBody($response);
        }
    }
}
