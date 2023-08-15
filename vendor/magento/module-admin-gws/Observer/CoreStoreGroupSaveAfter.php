<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\Framework\Event\ObserverInterface;

class CoreStoreGroupSaveAfter implements ObserverInterface
{
    /**
     * @var \Magento\AdminGws\Model\Models
     */
    protected $models;

    /**
     * @param \Magento\AdminGws\Model\Models $models
     */
    public function __construct(
        \Magento\AdminGws\Model\Models $models
    ) {
        $this->models = $models;
    }

    /**
     * Update role store group ids in helper and role
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->models->coreStoreGroupSaveAfter($observer);
    }
}
