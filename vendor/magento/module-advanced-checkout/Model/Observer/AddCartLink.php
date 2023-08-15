<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model\Observer;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\Framework\Event\ObserverInterface;

class AddCartLink implements ObserverInterface
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
     * Add link to cart in cart sidebar to view grid with failed products
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if (!$block instanceof \Magento\Checkout\Block\Cart\Sidebar) {
            return;
        }

        $failedItemsCount = count($this->_cart->getFailedItems());
        if ($failedItemsCount > 0) {
            $block->setAllowCartLink(true);
            $block->setCartEmptyMessage(__('%1 item(s) need your attention.', $failedItemsCount));
        }
    }
}
