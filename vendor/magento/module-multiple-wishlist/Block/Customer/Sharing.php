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
class Sharing extends \Magento\Wishlist\Block\Customer\Sharing
{
    /**
     * Wishlist data
     *
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $_wishlistData = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Wishlist\Model\Config $wishlistConfig
     * @param \Magento\Framework\Session\Generic $wishlistSession
     * @param \Magento\Wishlist\Helper\Data $wishlistData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Wishlist\Model\Config $wishlistConfig,
        \Magento\Framework\Session\Generic $wishlistSession,
        \Magento\Wishlist\Helper\Data $wishlistData,
        array $data = []
    ) {
        $this->_wishlistData = $wishlistData;
        parent::__construct($context, $wishlistConfig, $wishlistSession, $data);
    }

    /**
     * Retrieve send form action URL
     *
     * @return string
     */
    public function getSendUrl()
    {
        return $this->getUrl('*/*/send', ['wishlist_id' => $this->_wishlistData->getWishlist()->getId()]);
    }

    /**
     * Retrieve back button url
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/index', ['wishlist_id' => $this->_wishlistData->getWishlist()->getId()]);
    }
}
