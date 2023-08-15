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
class Copy extends \Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column\Management
{
    /**
     * Checks whether column should be shown in table
     *
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * Check wheter multiple wishlist functionality is enabled
     *
     * @return bool
     */
    public function isMultipleEnabled()
    {
        return $this->_wishlistHelper->isMultipleEnabled();
    }

    /**
     * Get wishlist item copy url
     *
     * @return string
     */
    public function getCopyItemUrl()
    {
        return $this->getUrl('wishlist/index/copyitem');
    }

    /**
     * Retrieve column javascript code
     *
     * @return string
     */
    public function getJs()
    {
        return parent::getJs() .
            "
            if (typeof Enterprise.Wishlist.url == 'undefined') {
                Enterprise.Wishlist.url = {};
            }
            Enterprise.Wishlist.url.copyItem = '" .
            $this->getCopyItemUrl() .
            "';
        ";
    }
}
