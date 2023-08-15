<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Update status of backup
 */
class UpdateStatusObserver implements ObserverInterface
{
    /**
     * @var \Magento\Support\Model\ResourceModel\Backup\Collection
     */
    protected $collection;

    /**
     * @param \Magento\Support\Model\ResourceModel\Backup\Collection $collection
     */
    public function __construct(\Magento\Support\Model\ResourceModel\Backup\Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Update Status for Backup and each item
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Magento\Support\Observer\UpdateStatusObserver
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->collection->addFieldToFilter('status', ['neq' => \Magento\Support\Model\Backup::STATUS_COMPLETE]);

        foreach ($this->collection as $backup) {
            $items = $backup->getItems();
            foreach ($items as $item) {
                $item->updateStatus();
            }
            $backup->updateStatus();
        }

        return $this;
    }
}
