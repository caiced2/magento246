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
use Magento\Framework\App\Utility\ReflectionClassFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use ReflectionException;
use SplFileInfo;

/**
 * Collects events for classes that contains $_eventPrefix variable.
 */
class EventPrefixesCollector
{
    /**
     * Array of events for Magento\Framework\Model\AbstractModel class
     *
     * @var array
     */
    private array $abstractModelEvents = [
        '_save_commit_after',
        '_save_after',
        '_delete_after',
        '_delete_commit_after',
    ];

    /**
     * @var NameFetcher
     */
    private NameFetcher $nameFetcher;

    /**
     * @var EventDataFactory
     */
    private EventDataFactory $eventDataFactory;

    /**
     * @var ReflectionClassFactory
     */
    private ReflectionClassFactory $reflectionClassFactory;

    /**
     * @param NameFetcher $nameFetcher
     * @param EventDataFactory $eventDataFactory
     * @param ReflectionClassFactory $reflectionClassFactory
     */
    public function __construct(
        NameFetcher $nameFetcher,
        EventDataFactory $eventDataFactory,
        ReflectionClassFactory $reflectionClassFactory
    ) {
        $this->nameFetcher = $nameFetcher;
        $this->eventDataFactory = $eventDataFactory;
        $this->reflectionClassFactory = $reflectionClassFactory;
    }

    /**
     * Collects events for classes that contains $_eventPrefix variable
     * and instance of Magento\Framework\Model\AbstractModel.
     * If the class is not an instance of mentioned above class we can't generate event codes for it.
     *
     * @param SplFileInfo $fileInfo
     * @param string $fileContent
     * @return array
     * @throws LocalizedException
     * @throws ReflectionException
     */
    public function fetchEvents(SplFileInfo $fileInfo, string $fileContent): array
    {
        $events = [];

        $className = $this->nameFetcher->getNameFromFile($fileInfo, $fileContent);
        $refClass = $this->reflectionClassFactory->create($className);

        preg_match('/\$_eventPrefix\s=\s(?<eventPrefix>(\'.*?\'|\".*?\"));/im', $fileContent, $matches);

        if (!isset($matches['eventPrefix'])) {
            throw new LocalizedException(
                __('Event prefix name cannot be fetched from the file: %1', $fileInfo->getPathname())
            );
        }

        $prefix = EventSubscriberInterface::EVENT_TYPE_OBSERVER . '.' . trim($matches['eventPrefix'], '\'"');
        if ($refClass->isSubclassOf(AbstractModel::class)) {
            foreach ($this->abstractModelEvents as $eventSuffix) {
                $eventName = $prefix . $eventSuffix;
                $events[$eventName] = $this->eventDataFactory->create([
                    EventData::EVENT_NAME => $eventName,
                    EventData::EVENT_CLASS_EMITTER => $className,
                ]);
            }
        }

        return $events;
    }
}
