<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\Framework\Event\ObserverInterface;

class RssCatalogReviewCollectionSelect implements ObserverInterface
{
    /**
     * @var \Magento\AdminGws\Model\Collections
     */
    private $collections;

    /**
     * @param \Magento\AdminGws\Model\Collections $collections
     */
    public function __construct(
        \Magento\AdminGws\Model\Collections $collections
    ) {
        $this->collections = $collections;
    }

    /**
     * Apply websites filter on collection used in review rss
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->collections->rssCatalogReviewCollectionSelect($observer);
    }
}
