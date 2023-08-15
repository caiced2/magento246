<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Model\ResourceModel\Item\Collection;

class Updater implements \Magento\Framework\View\Layout\Argument\UpdaterInterface
{
    /**
     * Wishlist data
     *
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $_wishlistData = null;

    /**
     * @param \Magento\Wishlist\Helper\Data $wishlistData
     */
    public function __construct(\Magento\Wishlist\Helper\Data $wishlistData)
    {
        $this->_wishlistData = $wishlistData;
    }

    /**
     * Add filtration by customer id
     *
     * @param \Magento\Framework\Data\Collection\AbstractDb $argument
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function update($argument)
    {
        $connection = $argument->getConnection();
        $defaultWishlistName = $this->_wishlistData->getDefaultWishlistName();
        $argument->getSelect()->columns(
            ['wishlist_name' => $connection->getIfNullSql('wishlist.name', $connection->quote($defaultWishlistName))]
        );

        $argument->addFilterToMap(
            'wishlist_name',
            $connection->getIfNullSql('wishlist.name', $connection->quote($defaultWishlistName))
        );
        return $argument;
    }
}
