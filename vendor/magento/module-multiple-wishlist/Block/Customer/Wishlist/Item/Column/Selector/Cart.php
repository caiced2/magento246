<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Wishlist item selector in wishlist table
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column\Selector;

/**
 * @api
 * @since 100.0.2
 */
class Cart extends \Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column\Selector
{
    /**
     * Retrieve block html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getItem()->getProduct()->isSaleable()) {
            return parent::_toHtml();
        }
        return '';
    }
}
