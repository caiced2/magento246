<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Wishlist item management column (copy, move, etc.)
 */
namespace Magento\MultipleWishlist\Block\Customer\Wishlist\Item\Column;

/**
 * @api
 * @since 100.0.2
 */
class Management extends \Magento\Wishlist\Block\Customer\Wishlist\Item\Column
{
    /**
     * Render block
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_wishlistHelper->isMultipleEnabled();
    }

    /**
     * Retrieve current customer wishlist collection
     *
     * @return \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection
     */
    public function getWishlists()
    {
        return $this->_wishlistHelper->getCustomerWishlists();
    }

    /**
     * Retrieve default wishlist for current customer
     *
     * @return \Magento\Wishlist\Model\Wishlist
     */
    public function getDefaultWishlist()
    {
        return $this->_wishlistHelper->getDefaultWishlist();
    }

    /**
     * Retrieve current wishlist
     *
     * @return \Magento\Wishlist\Model\Wishlist
     */
    public function getCurrentWishlist()
    {
        return $this->_wishlistHelper->getWishlist();
    }

    /**
     * Check whether user multiple wishlist limit reached
     *
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $wishlists
     * @return bool
     */
    public function canCreateWishlists(\Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $wishlists)
    {
        $customer = $this->_wishlistHelper->getCustomer();
        if (!$customer) {
            return false;
        }
        return !$this->_wishlistHelper->isWishlistLimitReached($wishlists) && $customer->getId();
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
}
