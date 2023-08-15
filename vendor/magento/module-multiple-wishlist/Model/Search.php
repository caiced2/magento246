<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Model;

/**
 * Wishlist search module
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Search
{
    /**
     * Wishlist collection factory
     *
     * @var \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory
     */
    protected $_wishlistCollectionFactory;

    /**
     * Construct
     *
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollectionFactory
     */
    public function __construct(
        \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollectionFactory
    ) {
        $this->_wishlistCollectionFactory = $wishlistCollectionFactory;
    }

    /**
     * Retrieve wishlist search results by search strategy
     *
     * @param \Magento\MultipleWishlist\Model\Search\Strategy\StrategyInterface $strategy
     * @return \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection
     */
    public function getResults(\Magento\MultipleWishlist\Model\Search\Strategy\StrategyInterface $strategy)
    {
        /* @var \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $collection */
        $collection = $this->_wishlistCollectionFactory->create();
        $collection->addFieldToFilter('visibility', ['eq' => 1]);
        $strategy->filterCollection($collection);
        return $collection;
    }
}
