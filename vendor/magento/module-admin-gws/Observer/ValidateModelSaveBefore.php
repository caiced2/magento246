<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\Framework\Event\ObserverInterface;

class ValidateModelSaveBefore implements ObserverInterface
{
    /**
     * @var \Magento\AdminGws\Model\Role
     */
    protected $role;

    /**
     * @var \Magento\AdminGws\Observer\RolePermissionAssigner
     */
    protected $rolePermissionAssigner;

    /**
     * @var \Magento\AdminGws\Model\CallbackInvoker
     */
    protected $callbackInvoker;

    /**
     * @var \Magento\AdminGws\Model\ConfigInterface
     */
    protected $config;

    /**
     * @param \Magento\AdminGws\Model\Role $role
     * @param \Magento\AdminGws\Observer\RolePermissionAssigner $rolePermissionAssigner
     * @param \Magento\AdminGws\Model\CallbackInvoker $callbackInvoker
     * @param \Magento\AdminGws\Model\ConfigInterface $config
     */
    public function __construct(
        \Magento\AdminGws\Model\Role $role,
        \Magento\AdminGws\Observer\RolePermissionAssigner $rolePermissionAssigner,
        \Magento\AdminGws\Model\CallbackInvoker $callbackInvoker,
        \Magento\AdminGws\Model\ConfigInterface $config
    ) {
        $this->rolePermissionAssigner = $rolePermissionAssigner;
        $this->callbackInvoker = $callbackInvoker;
        $this->role = $role;
        $this->config = $config;
    }

    /**
     * Validate / update a model before saving it
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->role->getIsAll()) {
            return;
        }

        $model = $observer->getEvent()->getObject();

        if (!($callback = $this->config->getCallbackForObject($model, 'model_save_before'))) {
            return;
        }

        $this->callbackInvoker
            ->invoke(
                $callback,
                $this->config->getGroupProcessor('model_save_before'),
                $model
            );
    }
}
