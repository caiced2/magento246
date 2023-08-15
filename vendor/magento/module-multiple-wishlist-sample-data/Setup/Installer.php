<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlistSampleData\Setup;

use Magento\Framework\Setup;

class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * @var \Magento\MultipleWishlistSampleData\Model\Wishlist
     */
    private $wishlist;

    /**
     * @param \Magento\MultipleWishlistSampleData\Model\Wishlist $wishlist
     */
    public function __construct(\Magento\MultipleWishlistSampleData\Model\Wishlist $wishlist) {
        $this->wishlist = $wishlist;
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->wishlist->delete(
            [
                'Magento_WishlistSampleData::fixtures/wishlist.csv',
                'Magento_MultipleWishlistSampleData::fixtures/wishlist.csv'
            ]
        );
        $this->wishlist->install(
            [
                'Magento_WishlistSampleData::fixtures/wishlist.csv',
                'Magento_MultipleWishlistSampleData::fixtures/wishlist.csv'
            ]
        );
    }
}
