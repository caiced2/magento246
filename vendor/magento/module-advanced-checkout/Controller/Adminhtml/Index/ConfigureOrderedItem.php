<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\Framework\Exception\LocalizedException;

class ConfigureOrderedItem extends \Magento\AdvancedCheckout\Controller\Adminhtml\Index
{
    /**
     * Create item
     *
     * @param string $itemId
     * @return \Magento\Sales\Model\Order\Item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createItem($itemId)
    {
        if (!$itemId) {
            throw new LocalizedException(
                __('Orders can only be completed in an active store. Verify the store and try again.')
            );
        }

        $item = $this->_objectManager->create(\Magento\Sales\Model\Order\Item::class)->load($itemId);
        if (!$item->getId()) {
            throw new LocalizedException(__('The ordered item needs to be loaded. Load the item and try again.'));
        }
        return $item;
    }

    /**
     * Ajax handler to configure item in wishlist
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        // Prepare data
        $configureResult = new \Magento\Framework\DataObject();
        try {
            $this->_initData();

            $customer = $this->_registry->registry('checkout_current_customer');
            $customerId = $customer instanceof \Magento\Customer\Model\Customer ? $customer->getId() : (int)$customer;
            $store = $this->_registry->registry('checkout_current_store');
            $storeId = $store instanceof \Magento\Store\Model\Store ? $store->getId() : (int)$store;

            $item = $this->createItem((int)$this->getRequest()->getParam('id'));

            $configureResult->setOk(true)
                ->setProductId($item->getProductId())
                ->setBuyRequest($item->getBuyRequest())
                ->setCurrentStoreId($storeId)
                ->setCurrentCustomerId($customerId);
        } catch (\Exception $e) {
            $configureResult->setError(true);
            $configureResult->setMessage($e->getMessage());
        }

        // Render page
        /* @var $helper \Magento\Catalog\Helper\Product\Composite */
        $helper = $this->_objectManager->get(\Magento\Catalog\Helper\Product\Composite::class);
        return $helper->renderConfigureResult($configureResult);
    }
}
