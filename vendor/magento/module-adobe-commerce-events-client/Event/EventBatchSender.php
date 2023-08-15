<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Lock\LockManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class for sending event data in batches to the configured events service
 */
class EventBatchSender
{
    private const LOCK_NAME = 'event_batch_sender';
    private const LOCK_TIMEOUT = 60;

    private const CONFIG_PATH_MAX_RETRIES = 'adobe_io_events/eventing/max_retries';

    private const EVENT_COUNT_PER_ITERATION = 1000;

    /**
     * @var LockManagerInterface
     */
    private LockManagerInterface $lockManager;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var EventRetrieverInterface
     */
    private EventRetrieverInterface $eventRetriever;

    /**
     * @var EventStatusUpdater
     */
    private EventStatusUpdater $statusUpdater;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param LockManagerInterface $lockManager
     * @param ScopeConfigInterface $config
     * @param Client $client
     * @param EventRetrieverInterface $eventRetriever
     * @param EventStatusUpdater $statusUpdater
     * @param LoggerInterface $logger
     */
    public function __construct(
        LockManagerInterface $lockManager,
        ScopeConfigInterface $config,
        Client $client,
        EventRetrieverInterface $eventRetriever,
        EventStatusUpdater $statusUpdater,
        LoggerInterface $logger
    ) {
        $this->lockManager = $lockManager;
        $this->config = $config;
        $this->client = $client;
        $this->eventRetriever = $eventRetriever;
        $this->statusUpdater = $statusUpdater;
        $this->logger = $logger;
    }

    /**
     * Sends events data in batches.
     *
     * Reads stored event data waiting to be sent, sends the data in batches to the Events Service, and updates stored
     * events based on the success or failure of sending the data.
     * Added locking mechanism to avoid the situation when two processes trying to process the same events.
     * Events are processed in iterations, during each iteration up to self::EVENT_COUNT_PER_ITERATION events
     * are selected.
     *
     * @return void
     * @throws LocalizedException
     */
    public function sendEventDataBatches(): void
    {
        do {
            $didLock = false;
            $waitingEvents = [];
            try {
                if (!$this->lockManager->lock(self::LOCK_NAME, self::LOCK_TIMEOUT)) {
                    return;
                }

                $didLock = true;
                $waitingEvents = $this->eventRetriever->getEventsWithLimit(self::EVENT_COUNT_PER_ITERATION);
                $this->statusUpdater->updateStatus(
                    array_keys($waitingEvents),
                    EventInterface::SENDING_STATUS
                );
            } finally {
                if ($didLock) {
                    $this->lockManager->unlock(self::LOCK_NAME);
                }
            }

            $waitingEventsCount = count($waitingEvents);
            if ($waitingEventsCount) {
                $this->processEvents($waitingEvents);
            }
        } while ($waitingEventsCount === self::EVENT_COUNT_PER_ITERATION);
    }

    /**
     * Processes array of events.
     *
     * Splits events into batches before sending.
     *
     * @param array $waitingEvents
     * @return void
     */
    private function processEvents(array $waitingEvents): void
    {
        $eventIds = array_keys($waitingEvents);
        $eventData = array_values($waitingEvents);

        try {
            $response = $this->client->sendEventDataBatch($eventData);

            if ($response->getStatusCode() == 200) {
                $this->logger->info(sprintf(
                    'Event data batch of %s events was successfully published.',
                    count($eventData)
                ));
                $this->statusUpdater->updateStatus($eventIds, EventInterface::SUCCESS_STATUS);
            } else {
                $errorMessage = sprintf(
                    'Error code: %d; reason: %s %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    $response->getBody()->getContents()
                );
                $this->setFailure($eventIds, $errorMessage);
            }
        } catch (Throwable $exception) {
            $this->setFailure($eventIds, $exception->getMessage());
        }
    }

    /**
     * Sets failure status from provided event ids and logs error message.
     *
     * @param array $eventIds
     * @param string $errorMessage
     * @return void
     */
    private function setFailure(array $eventIds, string $errorMessage): void
    {
        $maxRetries = (int)$this->config->getValue(self::CONFIG_PATH_MAX_RETRIES);
        $this->logger->error(sprintf(
            'Publishing of batch of %s events failed: %s',
            count($eventIds),
            $errorMessage
        ));
        $this->statusUpdater->updateFailure($eventIds, $maxRetries, 'Event publishing failed: ' . $errorMessage);
    }
}
