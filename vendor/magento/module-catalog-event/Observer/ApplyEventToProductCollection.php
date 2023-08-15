<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Observer;

use Magento\Framework\Event\ObserverInterface;

class ApplyEventToProductCollection implements ObserverInterface
{
    /**
     * @var \Magento\CatalogEvent\Helper\Data
     */
    protected $catalogEventData;

    /**
     * @var ProductEventApplier
     */
    protected $eventApplier;

    /**
     * @param \Magento\CatalogEvent\Helper\Data $catalogEventData
     * @param ProductEventApplier $eventApplier
     */
    public function __construct(\Magento\CatalogEvent\Helper\Data $catalogEventData, ProductEventApplier $eventApplier)
    {
        $this->catalogEventData = $catalogEventData;
        $this->eventApplier = $eventApplier;
    }

    /**
     * Applies events to product collection
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->catalogEventData->isEnabled()) {
            return $this;
        }

        $collection = $observer->getEvent()->getCollection();
        $collection->addCategoryIds();
        foreach ($collection as $product) {
            $this->eventApplier->applyEventToProduct($product);
        }
        return $this;
    }
}
