<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;

/**
 * Multiple Wishlist section
 */
class MultipleWishlist implements SectionSourceInterface
{
    /**
     * @var \Magento\MultipleWishlist\Helper\Data
     */
    protected $wishlistHelper;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @param \Magento\MultipleWishlist\Helper\Data $wishlistHelper
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     */
    public function __construct(
        \Magento\MultipleWishlist\Helper\Data $wishlistHelper,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
    ) {
        $this->wishlistHelper = $wishlistHelper;
        $this->currentCustomer = $currentCustomer;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        return [
            'can_create' => $this->canCreateWishlists(),
            'short_list' => $this->getWishlistShortList(),
        ];
    }

    /**
     * Check whether customer reached wishlist limit
     *
     * @return bool
     */
    protected function canCreateWishlists()
    {
        $customerId = $this->currentCustomer->getCustomerId();
        return !$this->wishlistHelper->isWishlistLimitReached($this->wishlistHelper->getCustomerWishlists())
            && $customerId;
    }

    /**
     * Get customer wishlist list
     *
     * @return array
     */
    protected function getWishlistShortList()
    {
        $wishlistData = [];
        foreach ($this->wishlistHelper->getCustomerWishlists() as $wishlist) {
            $wishlistData[] = ['id' => $wishlist->getId(), 'name' => $wishlist->getName()];
        }
        return $wishlistData;
    }
}
