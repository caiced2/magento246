<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Config\Reader;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\LocalizedException;

/**
 * Returns list of all subscribed events
 *
 * @api
 * @since 1.1.0
 */
class EventList
{
    /**
     * @var Reader
     */
    private Reader $reader;

    /**
     * @var DeploymentConfig
     */
    private DeploymentConfig $deploymentConfig;

    /**
     * @var EventFactory
     */
    private EventFactory $eventFactory;

    /**
     * @var Event[]|null
     */
    private ?array $events = null;

    /**
     * @param Reader $reader
     * @param DeploymentConfig $deploymentConfig
     * @param EventFactory $eventFactory
     */
    public function __construct(Reader $reader, DeploymentConfig $deploymentConfig, EventFactory $eventFactory)
    {
        $this->reader = $reader;
        $this->deploymentConfig = $deploymentConfig;
        $this->eventFactory = $eventFactory;
    }

    /**
     * Returns list of all subscribed events.
     *
     * @return Event[]
     * @throws EventInitializationException
     */
    public function getAll(): array
    {
        if ($this->events === null) {
            $this->load();
        }

        return $this->events;
    }

    /**
     * Returns event object by event name.
     *
     * @param string $eventName
     * @return Event|null
     * @throws EventInitializationException
     */
    public function get(string $eventName): ?Event
    {
        if ($this->events === null) {
            $this->load();
        }

        return $this->events[$this->removeCommercePrefix($eventName)] ?? null;
    }

    /**
     * Check that event or events based on this event is enabled.
     *
     * @param string $eventName
     * @return bool
     * @throws EventInitializationException
     */
    public function isEventEnabled(string $eventName): bool
    {
        $eventName = $this->removeCommercePrefix($eventName);

        foreach ($this->getAll() as $event) {
            if ($event->isBasedOn($eventName) && $event->isEnabled()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resets list of loaded events.
     *
     * Should be called after subscription or unsubscription to events to load the updated configuration.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->events = null;
    }

    /**
     * Loads IO events subscription configuration from io_events.xml file and deployment configuration
     *
     * @return void
     * @throws EventInitializationException
     */
    private function load(): void
    {
        try {
            $requiredEvents = $this->reader->read();

            $events = [];
            foreach ($requiredEvents as $eventData) {
                $events[$eventData['name']] = $this->eventFactory->create($eventData);
            }

            $optionalEvents = $this->deploymentConfig->get(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME);
            if (!empty($optionalEvents) && is_array($optionalEvents)) {
                foreach ($optionalEvents as $eventName => $eventData) {
                    $eventName = strtolower($eventName);
                    if (isset($requiredEvents[$eventName])) {
                        continue;
                    }
                    $this->validateEventData($eventName, $eventData);

                    $events[$eventName] = $this->eventFactory->create([
                        Event::EVENT_NAME => $eventName,
                        Event::EVENT_PARENT => $eventData['parent'] ?? null,
                        Event::EVENT_OPTIONAL => true,
                        Event::EVENT_FIELDS => $eventData['fields'],
                        Event::EVENT_RULES => $eventData['rules'] ?? [],
                        Event::EVENT_ENABLED => !isset($eventData['enabled']) || $eventData['enabled'] === 1,
                        Event::EVENT_PRIORITY =>
                            isset($eventData[Event::EVENT_PRIORITY]) && $eventData[Event::EVENT_PRIORITY] === 1,
                    ]);
                }
            }

            $this->events = $events;
        } catch (LocalizedException $e) {
            throw new EventInitializationException(__($e->getMessage()), $e);
        }
    }

    /**
     * Validates that $eventData is correctly configured.
     *
     * The $eventData must be an array and contain fields parameter.
     *
     * @param string $eventName
     * @param mixed $eventData
     * @return void
     * @throws InvalidConfigurationException if event configuration is not valid
     */
    public function validateEventData(string $eventName, $eventData): void
    {
        if (!is_array($eventData) || empty($eventData['fields'])) {
            throw new InvalidConfigurationException(
                __(
                    'Wrong configuration in "%1" section of app/etc/config.php file for the event "%2". ' .
                    'The configuration must be in array format with at least one field configured.',
                    EventSubscriberInterface::IO_EVENTS_CONFIG_NAME,
                    $eventName
                )
            );
        }
    }

    /**
     * Removes commerce prefix from the event name.
     *
     * @param string $eventName
     * @return string
     */
    public function removeCommercePrefix(string $eventName): string
    {
        return str_replace(EventSubscriberInterface::EVENT_PREFIX_COMMERCE, '', $eventName);
    }
}
