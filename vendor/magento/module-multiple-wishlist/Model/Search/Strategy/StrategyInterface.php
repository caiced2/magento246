<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Model\Search\Strategy;

use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection;

/**
 * Wishlist search strategy interface
 *
 * Interface \Magento\MultipleWishlist\Model\Search\Strategy\StrategyInterface
 *
 * @api
 */
interface StrategyInterface
{
    /**
     * Filter given wishlist collection
     *
     * @abstract
     * @param Collection $collection
     * @return Collection
     */
    public function filterCollection(Collection $collection);
}
