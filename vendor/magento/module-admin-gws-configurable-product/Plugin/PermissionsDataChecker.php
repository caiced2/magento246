<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGwsConfigurableProduct\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\ConfigurableProduct\Block\DataProviders\PermissionsData;

/**
 * Check permissions for current user
 */
class PermissionsDataChecker
{
    /**
     * @var Role
     */
    private $role;

    /**
     * Initialize dependencies.
     *
     * @param Role $role
     */
    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    /**
     * Check permissions for current user
     *
     * @param PermissionsData $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsAllowedToManageAttributes(PermissionsData $subject, bool $result): bool
    {
        if (!$this->role->getIsAll()) {
            return false;
        }

        return $result;
    }
}
