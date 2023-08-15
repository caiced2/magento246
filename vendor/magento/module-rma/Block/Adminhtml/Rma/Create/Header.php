<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Create;

/**
 * Admin RMA create form header
 */
class Header extends \Magento\Rma\Block\Adminhtml\Rma\Create\AbstractCreate
{
    /**
     * Create new rma content
     *
     * @return string
     */
    protected function _toHtml()
    {
        $customerId = $this->getCustomerId();
        $storeId = $this->getStoreId();
        $out = '';
        if ($customerId && $storeId) {
            $storeName = $this->_storeManager->getStore($storeId)->getName();
            $customerName = $this->getCustomerName();
            $out .= __('Create New RMA for %1 in %2', $customerName, $storeName);
        } elseif ($customerId) {
            $out .= __('Create New RMA for %1', $this->getCustomerName());
        } else {
            $out .= __('Create New RMA');
        }
        $out = $this->escapeHtml($out);
        $out = '<h3 class="icon-head head-sales-order">' . $out . '</h3>';
        return $out;
    }
}
