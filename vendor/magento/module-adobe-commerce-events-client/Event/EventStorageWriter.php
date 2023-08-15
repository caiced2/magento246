<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Api\EventRepositoryInterface;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter\CreateEventValidator;
use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataException;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Queue\Publisher\EventPublisherFactory;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\AdobeCommerceEventsClient\Model\EventFactory as EventModelFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Psr\Log\LoggerInterface;

/**
 * Writes new event data to storage.
 *
 * @api
 * @since 1.1.0
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EventStorageWriter
{
    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var EventRepositoryInterface
     */
    private EventRepositoryInterface $eventRepository;

    /**
     * @var EventModelFactory
     */
    private EventModelFactory $eventModelFactory;

    /**
     * @var DataFilterInterface
     */
    private DataFilterInterface $eventDataFilter;

    /**
     * @var EventMetadataCollector
     */
    private EventMetadataCollector $metadataCollector;

    /**
     * @var CreateEventValidator
     */
    private CreateEventValidator $createEventValidator;

    /**
     * @var EventPublisherFactory
     */
    private EventPublisherFactory $eventPublisherFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param EventList $eventList
     * @param CreateEventValidator $createEventValidator
     * @param EventRepositoryInterface $eventRepository
     * @param EventModelFactory $eventModelFactory
     * @param DataFilterInterface $eventDataFilter
     * @param EventMetadataCollector $metadataCollector
     * @param EventPublisherFactory $eventPublisherFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventList $eventList,
        CreateEventValidator $createEventValidator,
        EventRepositoryInterface $eventRepository,
        EventModelFactory $eventModelFactory,
        DataFilterInterface $eventDataFilter,
        EventMetadataCollector $metadataCollector,
        EventPublisherFactory $eventPublisherFactory,
        LoggerInterface $logger
    ) {
        $this->eventList = $eventList;
        $this->createEventValidator = $createEventValidator;
        $this->eventRepository = $eventRepository;
        $this->eventModelFactory = $eventModelFactory;
        $this->eventDataFilter = $eventDataFilter;
        $this->metadataCollector = $metadataCollector;
        $this->eventPublisherFactory = $eventPublisherFactory;
        $this->logger = $logger;
    }

    /**
     * Checks if there are registered events that depend on this eventCode.
     *
     * Creates events for all appropriate registration.
     *
     * @param string $eventCode
     * @param array $eventData
     * @return void
     * @throws EventException
     * @throws EventInitializationException
     */
    public function createEvent(string $eventCode, array $eventData): void
    {
        $eventCode = $this->eventList->removeCommercePrefix($eventCode);
        foreach ($this->eventList->getAll() as $event) {
            if ($event->isEnabled() && $event->isBasedOn($eventCode)) {
                $this->saveEvent($event, $eventData);
            }
        }
    }

    /**
     * Creates an Event with the specified event code and event data and adds it to storage.
     *
     * @param Event $event
     * @param array $eventData
     * @return void
     * @throws EventException
     * @throws EventInitializationException
     */
    private function saveEvent(Event $event, array $eventData): void
    {
        $eventCode = EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $event->getName();
        try {
            if (!$this->createEventValidator->validate($event, $eventData)) {
                return;
            }

            $eventModel = $this->eventModelFactory->create();
            $eventModel->setEventCode($eventCode);
            $eventModel->setEventData($this->eventDataFilter->filter($eventCode, $eventData));
            $eventModel->setMetadata($this->metadataCollector->getMetadata());
            $eventModel->setPriority((int)$event->isPriority());

            $this->eventRepository->save($eventModel);
            if ($event->isPriority()) {
                $publisher = $this->eventPublisherFactory->create();
                $publisher->execute($eventModel->getId());
            }
        } catch (AlreadyExistsException $e) {
            $this->logger->error(sprintf(
                'Could not create event "%s": %s',
                $eventCode,
                $e->getMessage()
            ));
        } catch (OperatorException $e) {
            $this->logger->error(sprintf(
                'Could not check that event "%s" passed the rule, error: %s',
                $eventCode,
                $e->getMessage()
            ));
        } catch (EventMetadataException $e) {
            $this->logger->error(sprintf(
                'Could not collect required metadata for the event "%s", error: %s',
                $eventCode,
                $e->getMessage()
            ));
        }
    }
}
