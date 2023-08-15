<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Api\EventRepositoryInterface;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event as EventResourceModel;
use Magento\Framework\Exception\LocalizedException;

/**
 * Updates events statuses.
 */
class EventStatusUpdater
{
    /**
     * @var EventRepositoryInterface
     */
    private EventRepositoryInterface $eventRepository;

    /**
     * @var EventResourceModel
     */
    private EventResourceModel $eventResourceModel;

    /**
     * @param EventRepositoryInterface $eventRepository
     * @param EventResourceModel $eventResourceModel
     */
    public function __construct(
        EventRepositoryInterface $eventRepository,
        EventResourceModel $eventResourceModel
    ) {
        $this->eventRepository = $eventRepository;
        $this->eventResourceModel = $eventResourceModel;
    }

    /**
     * Updates statuses for the stored events with the specified ids to match the specified status code.
     *
     * Using resource model for the performance optimization.
     *
     * @param array $eventIds
     * @param int $statusCode
     * @param string $info
     * @return void
     * @throws LocalizedException
     */
    public function updateStatus(array $eventIds, int $statusCode, string $info = ''): void
    {
        $connection = $this->eventResourceModel->getConnection();

        $connection->update(
            $this->eventResourceModel->getMainTable(),
            ['status' => $statusCode, 'info' => $info],
            ['event_id in (?)' => $eventIds]
        );
    }

    /**
     * Updates stored events with the specified ids to reflect unsuccessful sending of event data by doing one of the
     * following:
     * - increments retries_count for an event if the incremented count is not greater than the configured maximum
     * number of retries
     * - otherwise sets the stored status for the event to reflect failure
     *
     * @param array $eventIds
     * @param int $maxRetries
     * @param string $info
     * @return void
     */
    public function updateFailure(array $eventIds, int $maxRetries, string $info = ''): void
    {
        foreach ($eventIds as $eventId) {
            $storedEvent = $this->eventRepository->getById((int)$eventId);
            $retries = $storedEvent->getRetriesCount() + 1;
            if ($retries > $maxRetries) {
                $storedEvent->setStatus(EventInterface::FAILURE_STATUS);
            } else {
                $storedEvent->setStatus(EventInterface::WAITING_STATUS);
                $storedEvent->setRetriesCount($retries);
            }
            if (!empty($info)) {
                $storedEvent->setInfo($info);
            }
            $this->eventRepository->save($storedEvent);
        }
    }
}
