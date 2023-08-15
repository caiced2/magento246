<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Synchronizer;

use Exception;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadataFactory;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeIoEventsClient\Console\CreateEventProvider;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;

/**
 * Register events metadata in Adobe I/O.
 */
class AdobeIoEventMetadataSynchronizer
{
    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var EventMetadataClient
     */
    private EventMetadataClient $metadataClient;

    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var AdobeIoEventMetadataFactory
     */
    private AdobeIoEventMetadataFactory $ioMetadataFactory;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param EventMetadataClient $metadataClient
     * @param EventList $eventList
     * @param AdobeIoEventMetadataFactory $metadataFactory
     */
    public function __construct(
        AdobeIOConfigurationProvider $configurationProvider,
        EventMetadataClient $metadataClient,
        EventList $eventList,
        AdobeIoEventMetadataFactory $metadataFactory
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->metadataClient = $metadataClient;
        $this->eventList = $eventList;
        $this->ioMetadataFactory = $metadataFactory;
    }

    /**
     * Register events metadata in Adobe I/O.
     *
     * @return array
     * @throws EventInitializationException
     * @throws SynchronizerException
     */
    public function run(): array
    {
        $events = $this->eventList->getAll();
        if (empty($events)) {
            return [];
        }

        $provider = $this->configurationProvider->getProvider();
        if ($provider === null) {
            throw new SynchronizerException(__(
                sprintf(
                    'Cannot register events metadata during setup:upgrade. ' .
                    'Run bin/magento %s to configure an event provider.',
                    CreateEventProvider::COMMAND_NAME
                )
            ));
        }

        try {
            $registeredEventMetadata = $this->metadataClient->listRegisteredEventMetadata($provider);
            $registeredEvents = [];
            foreach ($registeredEventMetadata as $eventMetadata) {
                $registeredEvents[] = $eventMetadata->getEventCode();
            }
        } catch (Exception $e) {
            throw new SynchronizerException(__(
                sprintf(
                    'Cannot register events metadata during setup:upgrade. ' .
                    'An error occurred while fetching previously registered events. Error: %s',
                    $e->getMessage()
                )
            ));
        }

        try {
            $messages = [];

            foreach ($events as $event) {
                $eventCode = EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $event->getName();

                if (in_array($eventCode, $registeredEvents)) {
                    continue;
                }

                $this->metadataClient->createEventMetadata(
                    $provider,
                    $this->ioMetadataFactory->generate($eventCode)
                );
                $messages[] = sprintf(
                    'Event metadata was registered for the event "%s"',
                    $event->getName()
                );
            }

            return $messages;
        } catch (Exception $e) {
            throw new SynchronizerException(__(
                sprintf(
                    'An error occurred while registering metadata for event %s. Error: %s',
                    $event->getName(),
                    $e->getMessage()
                )
            ));
        }
    }
}
