<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Cart;

class AddFailedItems extends \Magento\AdvancedCheckout\Controller\Cart
{
    /**
     * Add failed items to cart
     *
     * @return void
     */
    public function execute()
    {
        $failedItemsCart = $this->_getFailedItemsCart()->removeAllAffectedItems();
        $failedItems = $this->getRequest()->getParam('failed', []);
        $cartItems = $this->getRequest()->getParam('cart', []);
        $failedItemsCart->updateFailedItems($failedItems, $cartItems);
        $failedItemsCart->saveAffectedProducts();
        $this->_redirect('checkout/cart');
    }
}
