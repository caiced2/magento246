<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Magento\Framework\Module\Dir;
use Magento\Framework\Module\FullModuleList;

/**
 * Collects a list of events for specific collector for all modules
 */
class AggregatedEventList
{
    /**
     * @var FullModuleList
     */
    private FullModuleList $fullModuleList;

    /**
     * @var CollectorInterface
     */
    private CollectorInterface $eventCollector;

    /**
     * @var Dir
     */
    private Dir $dir;

    /**
     * @param FullModuleList $fullModuleList
     * @param CollectorInterface $eventCollector
     * @param Dir $dir
     */
    public function __construct(
        FullModuleList $fullModuleList,
        CollectorInterface $eventCollector,
        Dir $dir
    ) {
        $this->fullModuleList = $fullModuleList;
        $this->eventCollector = $eventCollector;
        $this->dir = $dir;
    }

    /**
     * Returns list of events of specific collector type
     *
     * @return EventData[]
     */
    public function getList(): array
    {
        $events = [];

        foreach ($this->fullModuleList->getAll() as $module) {
            if (strpos($module['name'], CollectorInterface::IGNORED_MODULES) !== false) {
                continue;
            }

            $modulePath = $this->dir->getDir($module['name']);
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $events = array_merge($events, $this->eventCollector->collect($modulePath));
        }

        ksort($events);

        return $events;
    }
}
