<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

class ApplyCoupon extends \Magento\AdvancedCheckout\Controller\Adminhtml\Index
{
    /**
     * Apply/cancel coupon code in quote, ajax
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->_isModificationAllowed();
            $this->_initData();
            if ($this->_redirectFlag) {
                return;
            }
            $code = $this->getRequest()->getPost('code', '');
            $quote = $this->_registry->registry('checkout_current_quote');
            $quote->setCouponCode($code)->collectTotals()->save();

            $this->_view->loadLayout();
            if (!$quote->getCouponCode()) {
                $this->_view->getLayout()->getBlock('form_coupon')->setInvalidCouponCode($code);
            }
            $this->_view->renderLayout();
        } catch (\Exception $e) {
            $this->_processException($e);
        }
    }
}
