<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Observer;

use Magento\Quote\Model\Quote;
use Magento\Framework\Event\ObserverInterface;

class ApplyEventOnQuoteItemSetQty implements ObserverInterface
{
    /**
     * Catalog event data
     *
     * @var \Magento\CatalogEvent\Helper\Data
     */
    protected $catalogEventData;

    /**
     * Event model factory
     *
     * @var \Magento\CatalogEvent\Model\Category\EventList
     */
    protected $categoryEventList;

    /**
     * @param \Magento\CatalogEvent\Helper\Data $catalogEventData
     * @param \Magento\CatalogEvent\Model\Category\EventList $eventList
     */
    public function __construct(
        \Magento\CatalogEvent\Helper\Data $catalogEventData,
        \Magento\CatalogEvent\Model\Category\EventList $eventList
    ) {
        $this->catalogEventData = $catalogEventData;
        $this->categoryEventList = $eventList;
    }

    /**
     * Applies events to product collection
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void|$this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->catalogEventData->isEnabled()) {
            return $this;
        }

        $item = $observer->getEvent()->getItem();
        /* @var $item \Magento\Quote\Model\Quote\Item */
        if ($item->getQuote()) {
            $this->_initializeEventsForQuoteItems($item->getQuote());
        }

        if ($item->getEventId()) {
            $event = $item->getEvent();
            if ($event) {
                if ($event->getStatus() !== \Magento\CatalogEvent\Model\Event::STATUS_OPEN) {
                    $item->setHasError(true)->setMessage(__('This product is no longer on sale.'));
                    $item->getQuote()->setHasError(
                        true
                    )->addMessage(
                        __('Some of these products can no longer be sold.')
                    );
                }
            } else {
                /*
                 * If quote item has event id but event was
                 * not assigned to it then we should set event id to
                 * null as event was removed already
                 */
                $item->setEventId(null);
            }
        }
    }

    /**
     * Initialize events for quote items
     *
     * @param Quote $quote
     * @return $this
     */
    protected function _initializeEventsForQuoteItems(Quote $quote)
    {
        if (!$quote->getEventInitialized()) {
            $quote->setEventInitialized(true);
            $eventIds = array_diff($quote->getItemsCollection()->getColumnValues('event_id'), [0]);

            if (!empty($eventIds)) {
                $collection = $this->categoryEventList->getEventCollection();
                $collection->addFieldToFilter('event_id', ['in' => $eventIds]);
                foreach ($collection as $event) {
                    $items = $quote->getItemsCollection()->getItemsByColumnValue('event_id', $event->getId());
                    foreach ($items as $quoteItem) {
                        $quoteItem->setEvent($event);
                    }
                }
            }
        }

        return $this;
    }
}
