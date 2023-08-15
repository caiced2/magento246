<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Exception;
use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event as EventResourceModel;
use Psr\Log\LoggerInterface;

/**
 * Handles deletion of events in the event_data database table that will no longer be processed for sending
 */
class EventStorageCleaner
{
    private const CONFIG_EVENT_RETENTION = 'adobe_io_events/eventing/event_retention';

    /**
     * @var EventResourceModel
     */
    private EventResourceModel $eventResourceModel;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @param EventResourceModel $eventResourceModel
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        EventResourceModel $eventResourceModel,
        LoggerInterface $logger,
        ScopeConfigInterface $config
    ) {
        $this->eventResourceModel = $eventResourceModel;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Deletes events in the event_data database table that will no longer be processed for sending due to their status.
     *
     * @return void
     */
    public function clean(): void
    {
        $deleteStatuses = [
            EventInterface::SUCCESS_STATUS,
            EventInterface::FAILURE_STATUS
        ];

        $eventRetentionTime = (int)$this->config->getValue(self::CONFIG_EVENT_RETENTION);
        $deleteCutoffTime = date(
            'Y-m-d h:i:s',
            strtotime(sprintf('-%u days', $eventRetentionTime))
        );

        $deleteConditions = [
            'status in (?)' => $deleteStatuses,
            'created_at <= ?' => $deleteCutoffTime
        ];

        try {
            $this->eventResourceModel->deleteConditionally($deleteConditions);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Unable to delete events: %s',
                    $e->getMessage()
                )
            );
        }
    }
}
