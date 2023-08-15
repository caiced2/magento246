<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Observer;

use \Magento\CatalogEvent\Model\Event;
use Magento\Framework\Event\ObserverInterface;

class ApplyIsSalableToProduct implements ObserverInterface
{
    /**
     * Apply is salable to product
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent()->getProduct()->getEvent();
        if ($event && in_array($event->getStatus(), [Event::STATUS_CLOSED, Event::STATUS_UPCOMING])) {
            $observer->getEvent()->getSalable()->setIsSalable(false);
        }
        return $this;
    }
}
