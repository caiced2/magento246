<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Review;

use Magento\AdminGws\Model\Role;
use Magento\AdminGws\Model\Collections;
use Magento\Review\Model\ResourceModel\Rating\Grid\Collection;

/**
 * Adds allowed stores to query filter.
 */
class RatingCollectionSizeLimiter
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @var Collections
     */
    private $gwsCollections;

    /**
     * @param Role $role
     * @param Collections $gwsCollections
     */
    public function __construct(
        Role $role,
        Collections $gwsCollections
    ) {
        $this->role = $role;
        $this->gwsCollections = $gwsCollections;
    }

    /**
     * Filtering query for retrieve correctly count of rating.
     *
     * @param Collection $collection
     * @return void
     */
    public function beforeGetSelectCountSql(Collection $collection): void
    {
        if (!$this->role->getIsAll()) {
            $this->gwsCollections->limitRatings($collection);
            $collection->getSelect()->group('main_table.rating_id');
        }
    }
}
