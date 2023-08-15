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
namespace Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column;

/**
 * @api
 * @since 100.0.2
 */
class Selector extends \Magento\Wishlist\Block\Customer\Wishlist\Item\Column
{
    /**
     * Render block
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_wishlistHelper->isMultipleEnabled() || $this->getIsEnabled();
    }
}
