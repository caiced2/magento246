<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\Framework\Event\ObserverInterface;

class UpdateRoleStores implements ObserverInterface
{
    /**
     * @var \Magento\AdminGws\Model\Role
     */
    protected $role;

    /**
     * @param \Magento\AdminGws\Model\Role $role
     */
    public function __construct(
        \Magento\AdminGws\Model\Role $role
    ) {
        $this->role = $role;
    }

    /**
     * Update store list which is available for role
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->role->setStoreIds(
            array_merge($this->role->getStoreIds(), [$observer->getStore()->getStoreId()])
        );
        return $this;
    }
}
