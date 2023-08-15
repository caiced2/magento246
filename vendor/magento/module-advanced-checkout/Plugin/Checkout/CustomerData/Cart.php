<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Plugin\Checkout\CustomerData;

class Cart
{
    /**
     * @var \Magento\AdvancedCheckout\Model\Cart
     */
    protected $cart;

    /**
     * @param \Magento\AdvancedCheckout\Model\Cart $cart
     * @codeCoverageIgnore
     */
    public function __construct(\Magento\AdvancedCheckout\Model\Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Add link to cart in cart sidebar to view grid with failed products
     *
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        $failedItemsCount = count($this->cart->getFailedItems());
        $result['cart_empty_message'] = $failedItemsCount > 0
            ? __('%1 item(s) need your attention.', $failedItemsCount)
            : '';
        return $result;
    }
}
