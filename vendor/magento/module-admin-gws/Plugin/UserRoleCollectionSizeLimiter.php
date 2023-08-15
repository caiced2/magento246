<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Authorization\Model\ResourceModel\Role\Grid\Collection;

/**
 * Limits collections size according to the allowed websites.
 */
class UserRoleCollectionSizeLimiter
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @param Role $role
     */
    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    /**
     * Adds allowed websites to query filter.
     *
     * @param Collection $subject
     */
    public function beforeGetSelectCountSql(Collection $subject): void
    {
        // don't need to filter websites for Admin user
        if (!$this->role->getIsAll()) {
            $subject->getSelect()->where('main_table.gws_websites IN (?)', $this->role->getRelevantWebsiteIds());
        }
    }
}
