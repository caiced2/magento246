<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

class GetShippingItemsGrid extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Return grid with shipping items for Ajax request
     *
     * @return void
     */
    public function execute()
    {
        $this->_initModel();
        $this->_view->loadLayout();
        $response = $this->_view->getLayout()->getBlock('magento_rma_getshippingitemsgrid')->toHtml();

        if (is_array($response)) {
            $this->getResponse()->representJson(
                $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($response)
            );
        } else {
            $this->getResponse()->setBody($response);
        }
    }
}
