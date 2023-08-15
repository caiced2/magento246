<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\Framework\Event\ObserverInterface;

class LimitCollection implements ObserverInterface
{
    /**
     * @var \Magento\AdminGws\Model\Role
     */
    protected $role;

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
     * @param \Magento\AdminGws\Model\CallbackInvoker $callbackInvoker
     * @param \Magento\AdminGws\Model\ConfigInterface $config
     */
    public function __construct(
        \Magento\AdminGws\Model\Role $role,
        \Magento\AdminGws\Model\CallbackInvoker $callbackInvoker,
        \Magento\AdminGws\Model\ConfigInterface $config
    ) {
        $this->callbackInvoker = $callbackInvoker;
        $this->role = $role;
        $this->config = $config;
    }

    /**
     * Limit a collection
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->role->getIsAll()) {
            return;
        }
        $collection = $observer->getEvent()->getCollection();
        if (!($callback = $this->config->getCallbackForObject($collection, 'collection_load_before'))) {
            return;
        }
        $this->callbackInvoker->invoke(
            $callback,
            $this->config->getGroupProcessor('collection_load_before'),
            $collection
        );
    }
}
