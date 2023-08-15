<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\Framework\Event\ObserverInterface;

class RestrictBlocks implements ObserverInterface
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
     * Apply restrictions to misc blocks before html
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->role->getIsAll()) {
            return;
        }
        if (!($block = $observer->getEvent()->getBlock())) {
            return;
        }
        if (!($callback = $this->config->getCallbackForObject($block, 'block_html_before'))
        ) {
            return;
        }
        /* the $observer is used intentionally */
        $this->callbackInvoker
            ->invoke(
                $callback,
                $this->config->getGroupProcessor('block_html_before'),
                $observer
            );
    }
}
