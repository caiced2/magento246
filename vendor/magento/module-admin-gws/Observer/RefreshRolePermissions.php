<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\Framework\Event\ObserverInterface;

class RefreshRolePermissions implements ObserverInterface
{
    /**
     * @var \Magento\AdminGws\Observer\RolePermissionAssigner
     */
    protected $rolePermissionAssigner;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

    /**
     * @param \Magento\AdminGws\Observer\RolePermissionAssigner $rolePermissionAssigner
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     */
    public function __construct(
        \Magento\AdminGws\Observer\RolePermissionAssigner $rolePermissionAssigner,
        \Magento\Backend\Model\Auth\Session $backendAuthSession
    ) {
        $this->rolePermissionAssigner = $rolePermissionAssigner;
        $this->backendAuthSession = $backendAuthSession;
    }

    /**
     * Refresh group/website/store permissions of the current admin user's role
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $user = $this->backendAuthSession->getUser();

        if ($user instanceof \Magento\User\Model\User) {
            $this->rolePermissionAssigner->assignRolePermissions($user->getRole());
        }
    }
}
