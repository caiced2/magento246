<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Wishlist delete button
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\MultipleWishlist\Block\Customer\Wishlist\Button;

/**
 * @api
 * @since 100.0.2
 */
class Delete extends \Magento\Wishlist\Block\AbstractBlock
{
    /**
     * Build block html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_wishlistHelper->isMultipleEnabled() && $this->isWishlistDeleteable()) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Check whether current wishlist can be deleted
     *
     * @return bool
     */
    protected function isWishlistDeleteable()
    {
        return !$this->_wishlistHelper->isWishlistDefault($this->getWishlistInstance());
    }

    /**
     * Build wishlist deletion url
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('wishlist/index/deletewishlist', ['wishlist_id' => '%item%']);
    }

    /**
     * Retrieve url to redirect customer to after wishlist is deleted
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getUrl('wishlist/index/index');
    }
}
