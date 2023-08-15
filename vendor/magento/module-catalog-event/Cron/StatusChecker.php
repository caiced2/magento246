<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Cron;

use \Magento\CatalogEvent\Model\Event;

class StatusChecker
{
    /**
     * @var \Magento\CatalogEvent\Model\Category\EventList
     */
    protected $categoryEventList;

    /**
     * @param \Magento\CatalogEvent\Model\Category\EventList $categoryEventList
     */
    public function __construct(
        \Magento\CatalogEvent\Model\Category\EventList $categoryEventList
    ) {
        $this->categoryEventList = $categoryEventList;
    }

    /**
     * Change CatalogEvent status by date and invalidate cache
     *
     * @return void
     */
    public function execute()
    {
        $eventCollection = $this->categoryEventList->getEventCollection()->addVisibilityFilter();
        /** @var \Magento\CatalogEvent\Model\Event $event */
        foreach ($eventCollection as $event) {
            if ($event->getDateStart() && $event->getDateEnd()) {
                $timeStart = (new \DateTime($event->getDateStart()))->getTimestamp();
                // Date already in gmt, no conversion
                $timeEnd = (new \DateTime($event->getDateEnd()))->getTimestamp();
                // Date already in gmt, no conversion
                $timeNow = gmdate('U');
                if ($timeStart <= $timeNow && $timeEnd >= $timeNow && $event->getStatus() == Event::STATUS_UPCOMING) {
                    $event->setStatus(Event::STATUS_OPEN);
                    $event->save();
                } elseif ($timeNow > $timeEnd && $event->getStatus() == Event::STATUS_OPEN) {
                    $event->setStatus(Event::STATUS_CLOSED);
                    $event->save();
                }
            }
        }
    }
}
