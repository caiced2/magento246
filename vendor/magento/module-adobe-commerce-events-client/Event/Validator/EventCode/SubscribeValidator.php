<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Validator\EventCode;

use Magento\AdobeCommerceEventsClient\Event\Collector\AggregatedEventList;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * Validates that plugin event can be subscribed too.
 * It's always possible to subscribe to observer events.
 */
class SubscribeValidator implements EventValidatorInterface
{
    /**
     * @var AggregatedEventList
     */
    private AggregatedEventList $aggregatedEventList;

    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @param AggregatedEventList $aggregatedEventList
     * @param EventList $eventList
     */
    public function __construct(AggregatedEventList $aggregatedEventList, EventList $eventList)
    {
        $this->aggregatedEventList = $aggregatedEventList;
        $this->eventList = $eventList;
    }

    /**
     * @inheritDoc
     *
     * @param Event $event
     * @param bool $force
     * @throws EventInitializationException
     * @throws ValidatorException
     */
    public function validate(Event $event, bool $force = false): void
    {
        if ($event->getParent()) {
            $supportedEvents = $this->aggregatedEventList->getList();
            if (isset($supportedEvents[$event->getName()])) {
                throw new ValidatorException(
                    __('"%1" cannot be used as the event code for a rule-based event. This event code ' .
                    'is already used by a supported event.', $event->getName())
                );
            }
        }

        $eventCode = $event->getParent() ?? $event->getName();
        $eventCodeParts = explode('.', $eventCode, 2);

        if ($eventCodeParts[0] !== EventSubscriberInterface::EVENT_TYPE_PLUGIN) {
            return;
        }

        $events = $this->eventList->getAll();

        if (isset($events[$eventCode])) {
            $subscribedEvent = $events[$eventCode];
            if (empty($event->getParent()) && (!$subscribedEvent->isOptional() || $subscribedEvent->isEnabled())) {
                throw new ValidatorException(
                    __('Event is already subscribed "%1" ', $eventCode)
                );
            }
        } elseif (!$force) {
            throw new ValidatorException(
                __(
                    'Could not register event "%1" because the required ' .
                    'classes have not been generated. ' . PHP_EOL .
                    'You can use the --force option to suppress this error. ' .
                    'You must subsequently generate the events module and perform dependency injection compilation.',
                    $eventCode
                )
            );
        }
    }
}
