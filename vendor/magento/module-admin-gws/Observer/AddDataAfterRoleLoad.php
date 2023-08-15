<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddDataAfterRoleLoad implements ObserverInterface
{
    /**
     * @var \Magento\AdminGws\Observer\RolePermissionAssigner
     */
    protected $rolePermissionAssigner;

    /**
     * @param \Magento\AdminGws\Observer\RolePermissionAssigner $rolePermissionAssigner
     */
    public function __construct(
        \Magento\AdminGws\Observer\RolePermissionAssigner $rolePermissionAssigner
    ) {
        $this->rolePermissionAssigner = $rolePermissionAssigner;
    }

    /**
     * Assign websites/stores permissions data after loading admin role
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->rolePermissionAssigner->assignRolePermissions($observer->getEvent()->getObject());
    }
}
