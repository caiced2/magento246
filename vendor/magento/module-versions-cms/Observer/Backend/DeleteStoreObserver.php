<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Observer\Backend;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class DeleteStoreObserver implements ObserverInterface
{
    /**
     * @var CleanStoreFootprints
     */
    protected $cleanStoreFootprints;

    /**
     * @param CleanStoreFootprints $cleanStoreFootprints
     */
    public function __construct(
        CleanStoreFootprints $cleanStoreFootprints
    ) {
        $this->cleanStoreFootprints = $cleanStoreFootprints;
    }

    /**
     * Clean up hierarchy tree that belongs to website.
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $storeId = $observer->getEvent()->getStore()->getId();
        $this->cleanStoreFootprints->clean($storeId);
    }
}
