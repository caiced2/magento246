<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model\Plugin;

use Magento\AdminGws\Model\Collections;
use Magento\User\Model\ResourceModel\User\Collection;

/**
 * Adds allowed websites or stores to query filter.
 */
class UserCollection
{
    /**
     * Gws collections model
     *
     * @var Collections
     */
    private $gwsCollections;

    /**
     * @param Collections $gwsCollections
     */
    public function __construct(Collections $gwsCollections)
    {
        $this->gwsCollections = $gwsCollections;
    }

    /**
     * Filtering query for retrieve correctly count of admin users
     *
     * @param Collection $collection
     * @return void
     */
    public function beforeGetSelectCountSql(Collection $collection): void
    {
        $this->gwsCollections->limitAdminPermissionUsers($collection);
    }
}
