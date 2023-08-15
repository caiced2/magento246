<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Collects and caches events from different collectors
 */
class CompositeCollector implements CollectorInterface
{
    private const CACHE_ID = 'composite_events_collector';

    /**
     * @var EventDataFactory
     */
    private EventDataFactory $eventDataFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @var CollectorInterface[]
     */
    private array $collectors;

    /**
     * @param EventDataFactory $eventDataFactory
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param array $collectors
     */
    public function __construct(
        EventDataFactory $eventDataFactory,
        CacheInterface $cache,
        SerializerInterface $serializer,
        array $collectors
    ) {
        $this->eventDataFactory = $eventDataFactory;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->collectors = $collectors;
    }

    /**
     * Collects events from the different collectors
     *
     * @param string $modulePath
     * @return EventData[]
     */
    public function collect(string $modulePath): array
    {
        $cacheId = $this->getCacheId($modulePath);
        $cachedEvents = $this->cache->load($cacheId);
        if ($cachedEvents && is_string($cachedEvents)) {
            return $this->unserializeEvents($cachedEvents);
        }

        $events = [];
        foreach ($this->collectors as $collector) {
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $events = array_merge($events, $collector->collect($modulePath));
        }
        $this->cache->save($this->serializeEvents($events), $cacheId);

        return $events;
    }

    /**
     * Returns cache identification.
     *
     * @param string $modulePath
     * @return string
     */
    private function getCacheId(string $modulePath): string
    {
         return self::CACHE_ID . '_' . implode('_', array_keys($this->collectors)) . '_' . $modulePath;
    }

    /**
     * Converts EventData object to simple array and serializes it.
     *
     * @param EventData[] $events
     * @return string
     */
    private function serializeEvents(array $events): string
    {
        $data = [];
        foreach ($events as $event) {
            $data[] = $event->getData();
        }

        return $this->serializer->serialize($data);
    }

    /**
     * Unserializes array of event data and creates array of EventData objects based on it.
     *
     * @param string $eventsData
     * @return array
     */
    private function unserializeEvents(string $eventsData): array
    {
        $events = [];

        $result = $this->serializer->unserialize($eventsData);
        if (is_array($result)) {
            foreach ($result as $data) {
                $events[$data[EventData::EVENT_NAME]] = $this->eventDataFactory->create($data);
            }
        }

        return $events;
    }
}
