<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Model\Indexer;

/**
 * Customer group state information.
 *
 * This class is used to save customer group IDs for the catalog permission indexer.
 * If we set customer group IDs,
 * They will be used in the filter along with category entity IDs during indexing of category permissions.
 */
class CustomerGroupFilter
{
    /**
     * @var array
     */
    private $groupIds = [];

    /**
     * Set customer group ids
     *
     * @param array $ids
     * @return array
     */
    public function setGroupIds(array $ids): array
    {
        return $this->groupIds = $ids;
    }

    /**
     * Get customer group ids
     *
     * @return array
     */
    public function getGroupIds(): array
    {
        return $this->groupIds;
    }
}
