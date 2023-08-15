<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Observer;

use Magento\Framework\Event\ObserverInterface;

class ApplyEventOnQuoteItemSetProduct implements ObserverInterface
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

        /* @var $product \Magento\Catalog\Model\Product */
        $product = $observer->getEvent()->getProduct();
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $observer->getEvent()->getQuoteItem();

        $this->eventApplier->applyEventToProduct($product);
        if ($product->getEvent()) {
            $quoteItem->setEventId($product->getEvent()->getId());
            if ($quoteItem->getParentItem()) {
                $quoteItem->getParentItem()->setEventId($quoteItem->getEventId());
            }
        }
        return $this;
    }
}
