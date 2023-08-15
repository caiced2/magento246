<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer;

use Magento\Customer\Api\Data\GroupInterface;

/**
 * Defines strategy for updating catalog permissions index
 *
 * @api
 * @since 100.2.0
 */
interface UpdateIndexInterface
{
    /**
     * Update price index
     *
     * @param GroupInterface $group
     * @param bool $isGroupNew
     * @return void
     * @since 100.2.0
     */
    public function update(GroupInterface $group, $isGroupNew);
}
