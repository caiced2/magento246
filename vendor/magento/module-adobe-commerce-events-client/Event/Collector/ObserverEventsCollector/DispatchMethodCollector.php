<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector\ObserverEventsCollector;

use Magento\AdobeCommerceEventsClient\Event\Collector\EventData;
use Magento\AdobeCommerceEventsClient\Event\Collector\EventDataFactory;
use Magento\AdobeCommerceEventsClient\Event\Collector\NameFetcher;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\Framework\Exception\LocalizedException;
use SplFileInfo;

/**
 * Collects event names from dispatch methods.
 */
class DispatchMethodCollector
{
    /**
     * @var NameFetcher
     */
    private NameFetcher $nameFetcher;

    /**
     * @var EventDataFactory
     */
    private EventDataFactory $eventDataFactory;

    /**
     * @param NameFetcher $nameFetcher
     * @param EventDataFactory $eventDataFactory
     */
    public function __construct(
        NameFetcher $nameFetcher,
        EventDataFactory $eventDataFactory
    ) {
        $this->nameFetcher = $nameFetcher;
        $this->eventDataFactory = $eventDataFactory;
    }

    /**
     * Parses and returns array of event names from dispatch methods.
     *
     * @param SplFileInfo $fileInfo
     * @param string $fileContent
     * @return array
     * @throws LocalizedException
     */
    public function fetchEvents(SplFileInfo $fileInfo, string $fileContent): array
    {
        $events = [];

        preg_match_all(
            '/->dispatch\([^\)\.]*?\n?[^\)\.]*?(?<eventName>(\'[^\']*\'|\"[^\"]*\"))\s*\,/im',
            $fileContent,
            $matches
        );

        if (!empty($matches['eventName'])) {
            $className = $this->nameFetcher->getNameFromFile($fileInfo, $fileContent);
            foreach ($matches['eventName'] as $eventName) {
                $eventName = trim($eventName, '"\'');
                if (strpos($eventName, '_before') === false) {
                    $eventName = EventSubscriberInterface::EVENT_TYPE_OBSERVER . '.' . $eventName;
                    $events[$eventName] = $this->eventDataFactory->create([
                        EventData::EVENT_NAME => $eventName,
                        EventData::EVENT_CLASS_EMITTER => $className,
                    ]);
                }
            }
        }

        return $events;
    }
}
