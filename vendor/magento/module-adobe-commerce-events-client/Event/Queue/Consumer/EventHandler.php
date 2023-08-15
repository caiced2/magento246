<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Queue\Consumer;

use Magento\AdobeCommerceEventsClient\Event\EventBatchSender;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Handler for sending events in batches.
 */
class EventHandler
{
    /**
     * @var EventBatchSender
     */
    private EventBatchSender $eventBatchSender;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param EventBatchSender $eventBatchSender
     * @param LoggerInterface $logger
     */
    public function __construct(EventBatchSender $eventBatchSender, LoggerInterface $logger)
    {
        $this->eventBatchSender = $eventBatchSender;
        $this->logger = $logger;
    }

    /**
     * Publishes batch of priority events.
     *
     * @param mixed $eventIds
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($eventIds): void
    {
        try {
            $this->eventBatchSender->sendEventDataBatches();
        } catch (LocalizedException $e) {
            $this->logger->error('Publishing of batch of events failed: ' . $e->getLogMessage());

            throw $e;
        }
    }
}
