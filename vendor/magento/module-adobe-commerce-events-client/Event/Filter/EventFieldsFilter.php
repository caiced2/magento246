<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter\Field;
use Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter\FieldConverter;

/**
 * Filters event payload according to the list of configured fields
 */
class EventFieldsFilter implements DataFilterInterface
{
    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var FieldConverter
     */
    private FieldConverter $converter;

    /**
     * @param EventList $eventList
     * @param FieldConverter $converter
     */
    public function __construct(
        EventList $eventList,
        FieldConverter $converter
    ) {
        $this->eventList = $eventList;
        $this->converter = $converter;
    }

    /**
     * @inheritDoc
     */
    public function filter(string $eventCode, array $eventData): array
    {
        $event = $this->eventList->get($eventCode);

        if (!$event instanceof Event || empty($event->getFields())) {
            return $eventData;
        }

        $filteredData = [];

        $fields = $this->converter->convert($event->getFields());

        foreach ($fields as $field) {
            $filteredData = array_replace_recursive(
                $filteredData,
                [$field->getName() => $this->processField($field, $eventData)]
            );
        }

        return $filteredData;
    }

    /**
     * Processes eventData filtering according to given Field.
     *
     * @param Field $field
     * @param array $eventData
     * @return array|mixed|null
     */
    private function processField(Field $field, array $eventData)
    {
        $filteredData = [];

        $children = $field->getChildren();

        if (empty($children)) {
            return $eventData[$field->getName()] ?? null;
        }

        if ($field->isArray()) {
            if (!isset($eventData[$field->getName()]) || !is_array($eventData[$field->getName()])) {
                return [];
            }

            foreach ($eventData[$field->getName()] as $eventDataItem) {
                $filteredData[] = $this->processChildren($children, is_array($eventDataItem) ? $eventDataItem : []);
            }
        } else {
            $filteredData = array_replace_recursive(
                $filteredData,
                $this->processChildren($children, $eventData[$field->getName()] ?? [])
            );
        }

        return $filteredData;
    }

    /**
     * Process children field elements
     *
     * @param Field[] $children
     * @param array $eventData
     * @return array
     */
    private function processChildren(array $children, array $eventData): array
    {
        $result = [];

        foreach ($children as $child) {
            if ($child->hasChildren()) {
                $result[$child->getName()] = $this->processField($child, $eventData);
            } else {
                $result[$child->getName()] = $eventData[$child->getName()] ?? null;
            }
        }

        return $result;
    }
}
