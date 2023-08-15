<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Validator\EventCode;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;

/**
 * Validates that event can be unsubscribed.
 */
class UnsubscribeValidator implements EventValidatorInterface
{
    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var DeploymentConfig
     */
    private DeploymentConfig $config;

    /**
     * @param EventList $eventList
     * @param DeploymentConfig $config
     */
    public function __construct(
        EventList $eventList,
        DeploymentConfig $config
    ) {
        $this->eventList = $eventList;
        $this->config = $config;
    }

    /**
     * Validates that event can be unsubscribed.
     *
     * It's possible to unsubscribe only from events that are registered in `io_events`
     * section in the app/etc/config.php file.
     *
     * @param Event $event
     * @param bool $force
     *
     * {@inheritDoc}
     *
     * @throws EventInitializationException
     * @throws ValidatorException
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function validate(Event $event, bool $force = false): void
    {
        $optionalIoEvents = $this->config->get(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME);

        $eventCode = $event->getName();
        if (!isset($optionalIoEvents[$eventCode])) {
            throw new ValidatorException(__(
                'Cannot unsubscribe "%1" because it is not registered in the "%2" section of the config.php file.',
                $eventCode,
                EventSubscriberInterface::IO_EVENTS_CONFIG_NAME
            ));
        }

        $events = $this->eventList->getAll();

        if (!isset($events[$eventCode])) {
            throw new ValidatorException(
                __('The "%1" event is not registered. You cannot unsubscribe from it.', $eventCode)
            );
        }

        $event = $events[$eventCode];
        if (!$event->isEnabled()) {
            throw new ValidatorException(
                __('The "%1" event has already been unsubscribed.', $eventCode)
            );
        }
    }
}
