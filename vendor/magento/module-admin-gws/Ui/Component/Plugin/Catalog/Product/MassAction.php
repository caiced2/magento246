<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Ui\Component\Plugin\Catalog\Product;

use Magento\AdminGws\Model\Role;

/**
 * Plugin for \Magento\Catalog\Ui\Component\Product\MassAction
 */
class MassAction
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @param Role $role
     */
    public function __construct(
        Role $role
    ) {
        $this->role = $role;
    }

    /**
     * Checks that mass update status and delete of products is not available to the admin with limited access.
     *
     * @param \Magento\Catalog\Ui\Component\Product\MassAction $massAction
     * @param bool $isActionAllowed
     * @param string $actionType
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsActionAllowed(
        \Magento\Catalog\Ui\Component\Product\MassAction $massAction,
        $isActionAllowed,
        $actionType
    ) {
        if ($isActionAllowed
            && in_array($actionType, ['status', 'delete'])
            && !$this->role->getIsAll()
        ) {
            $isActionAllowed = false;
        }

        return $isActionAllowed;
    }
}
