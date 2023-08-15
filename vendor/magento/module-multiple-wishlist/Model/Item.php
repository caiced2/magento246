<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Model;

/**
 * Enterprise wishlist item
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Item extends \Magento\Wishlist\Model\Item
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\MultipleWishlist\Model\ResourceModel\Item::class);
    }
}
