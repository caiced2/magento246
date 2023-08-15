<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

class ConfigureProductToAdd extends \Magento\AdvancedCheckout\Controller\Adminhtml\Index
{
    /**
     * Ajax handler to response configuration fieldset of composite product in order
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $this->_initData();
        $customer = $this->_registry->registry('checkout_current_customer');
        $store = $this->_registry->registry('checkout_current_store');

        $storeId = $store instanceof \Magento\Store\Model\Store ? $store->getId() : (int)$store;
        $customerId = $customer instanceof \Magento\Customer\Model\Customer ? $customer->getId() : (int)$customer;

        // Prepare data
        $productId = (int)$this->getRequest()->getParam('id');

        $configureResult = new \Magento\Framework\DataObject();
        $configureResult->setOk(true)
            ->setProductId($productId)
            ->setCurrentStoreId($storeId)
            ->setCurrentCustomerId($customerId);

        // Render page
        /* @var $helper \Magento\Catalog\Helper\Product\Composite */
        $helper = $this->_objectManager->get(\Magento\Catalog\Helper\Product\Composite::class);
        return $helper->renderConfigureResult($configureResult);
    }
}
