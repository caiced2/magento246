<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Util\EventCodeConverter;
use ReflectionClass;
use ReflectionMethod;

/**
 * Collects event methods for the provided class
 */
class EventMethodCollector
{
    /**
     * @var EventCodeConverter
     */
    private EventCodeConverter $eventCodeConverter;

    /**
     * @var EventDataFactory
     */
    private EventDataFactory $eventDataFactory;

    /**
     * @var MethodFilter
     */
    private MethodFilter $methodFilter;

    /**
     * @param EventDataFactory $eventDataFactory
     * @param EventCodeConverter $eventCodeConverter
     * @param MethodFilter $methodFilter
     */
    public function __construct(
        EventDataFactory $eventDataFactory,
        EventCodeConverter $eventCodeConverter,
        MethodFilter $methodFilter
    ) {
        $this->eventDataFactory = $eventDataFactory;
        $this->eventCodeConverter = $eventCodeConverter;
        $this->methodFilter = $methodFilter;
    }

    /**
     * Collects public methods for the provided class and converts them to EventData
     *
     * @param ReflectionClass $reflectionClass
     * @return EventData[]
     */
    public function collect(ReflectionClass $reflectionClass): array
    {
        $events = [];

        $className = $reflectionClass->getName();
        $methodList = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methodList as $method) {
            $methodName = $method->getName();
            if (empty($methodName) || $this->methodFilter->isExcluded($methodName)) {
                continue;
            }

            $eventName = EventSubscriberInterface::EVENT_TYPE_PLUGIN . '.' .
                $this->eventCodeConverter->convertToEventName($className, $methodName);

            $events[$eventName] = $this->eventDataFactory->create([
                EventData::EVENT_NAME => $eventName,
                EventData::EVENT_CLASS_EMITTER => $className,
            ]);
        }

        return $events;
    }
}
