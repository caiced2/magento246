<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model\Observer;

use Magento\AdvancedCheckout\Model\Cart;

/**
 * Advanced Checkout Cart Provider
 */
class CartProvider
{
    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * @param Cart $cart
     * @codeCoverageIgnore
     */
    public function __construct(Cart $cart)
    {
        $this->_cart = $cart;
    }

    /**
     * Returns cart model for backend
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return Cart
     */
    public function get(\Magento\Framework\Event\Observer $observer)
    {
        $storeId = $observer->getRequestModel()->getParam('storeId');
        if ($storeId === null || $storeId === '') {
            $storeId = $observer->getRequestModel()->getParam('store_id');

            if ($storeId === null || $storeId === '') {
                $storeId = $observer->getSession()->getStoreId();
            }
        }
        return $this->_cart->setSession(
            $observer->getSession()
        )->setContext(
            Cart::CONTEXT_ADMIN_ORDER
        )->setCurrentStore(
            (int)$storeId
        );
    }
}
