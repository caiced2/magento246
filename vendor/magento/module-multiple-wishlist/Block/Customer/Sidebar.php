<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Wishlist sidebar block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\MultipleWishlist\Block\Customer;

/**
 * @api
 * @since 100.0.2
 */
class Sidebar extends \Magento\Wishlist\Block\Customer\Sidebar
{
    /**
     * @var \Magento\MultipleWishlist\Helper\Data
     */
    protected $_multipleWishlistHelper;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\MultipleWishlist\Helper\Data $multipleWishlistHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\MultipleWishlist\Helper\Data $multipleWishlistHelper,
        array $data = []
    ) {
        $this->_multipleWishlistHelper = $multipleWishlistHelper;
        parent::__construct(
            $context,
            $httpContext,
            $data
        );
    }

    /**
     * Retrieve wishlist helper
     *
     * @return \Magento\MultipleWishlist\Helper\Data
     */
    protected function _getHelper()
    {
        return $this->_multipleWishlistHelper;
    }

    /**
     * Retrieve block title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTitle()
    {
        if ($this->_getHelper()->isMultipleEnabled()) {
            return __('My Wish Lists');
        } else {
            return parent::getTitle();
        }
    }

    /**
     * Create wishlist item collection
     *
     * @return \Magento\Wishlist\Model\ResourceModel\Item\Collection
     */
    protected function _createWishlistItemCollection()
    {
        return $this->_getHelper()->getWishlistItemCollection();
    }
}
