<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection;

/**
 * Limits collections size according to the allowed websites.
 */
class CollectionSizeLimiter
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
     * @param AbstractCollection $subject
     */
    public function beforeGetSelectCountSql(AbstractCollection $subject): void
    {
        // don't need to filter websites for Admin user
        if (!$this->role->getIsAll()) {
            $subject->addWebsiteFilter($this->role->getRelevantWebsiteIds());
        }
    }
}
