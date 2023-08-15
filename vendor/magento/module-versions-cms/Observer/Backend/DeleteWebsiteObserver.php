<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Observer\Backend;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class DeleteWebsiteObserver implements ObserverInterface
{
    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory
     */
    protected $hierarchyNodeFactory;

    /**
     * @var CleanStoreFootprints
     */
    protected $cleanStoreFootprints;

    /**
     * @param \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory
     * @param CleanStoreFootprints $cleanStoreFootprints
     */
    public function __construct(
        \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory,
        CleanStoreFootprints $cleanStoreFootprints
    ) {
        $this->hierarchyNodeFactory = $hierarchyNodeFactory;
        $this->cleanStoreFootprints = $cleanStoreFootprints;
    }

    /**
     * Clean up hierarchy tree that belongs to website.
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Store\Model\Website $website */
        $website = $observer->getEvent()->getWebsite();

        $this->hierarchyNodeFactory->create()->deleteByScope(
            \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_WEBSITE,
            $website->getId()
        );

        foreach ($website->getStoreIds() as $storeId) {
            $this->cleanStoreFootprints->clean($storeId);
        }

        return $this;
    }
}
