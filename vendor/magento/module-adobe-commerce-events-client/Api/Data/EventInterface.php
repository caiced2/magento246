<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Api\Data;

use Magento\AdobeCommerceEventsClient\Model\EventException;

/**
 * Defines the event database model
 *
 * @api
 * @since 1.1.0
 */
interface EventInterface
{
    public const FIELD_ID = 'event_id';
    public const FIELD_CODE = 'event_code';
    public const FIELD_DATA = 'event_data';
    public const FIELD_METADATA = 'metadata';
    public const FIELD_RETRIES = 'retries_count';
    public const FIELD_STATUS = 'status';
    public const FIELD_INFO = 'info';
    public const FIELD_PRIORITY = 'priority';

    public const WAITING_STATUS = 0;
    public const SUCCESS_STATUS = 1;
    public const FAILURE_STATUS = 2;
    public const SENDING_STATUS = 3;

    public const PRIORITY_HIGH = 1;

    /**
     * Returns event id.
     *
     * @return null|string
     */
    public function getId(): ?string;

    /**
     * Returns event code.
     *
     * @return null|string
     */
    public function getEventCode(): ?string;

    /**
     * Sets event code.
     *
     * @param string $eventCode
     * @return EventInterface
     */
    public function setEventCode(string $eventCode): EventInterface;

    /**
     * Returns event data.
     *
     * @return array
     * @throws EventException
     */
    public function getEventData(): array;

    /**
     * Sets event data.
     *
     * @param array $eventData
     * @return EventInterface
     * @throws EventException
     */
    public function setEventData(array $eventData): EventInterface;

    /**
     * Returns event metadata.
     *
     * @return array
     * @throws EventException
     */
    public function getMetadata(): array;

    /**
     * Sets event metadata.
     *
     * @param array $metadata
     * @return EventInterface
     * @throws EventException
     */
    public function setMetadata(array $metadata): EventInterface;

    /**
     * Sets event status.
     *
     * @param int $statusCode
     * @return EventInterface
     */
    public function setStatus(int $statusCode): EventInterface;

    /**
     * Returns count of retries event sending.
     *
     * @return int
     */
    public function getRetriesCount(): int;

    /**
     * Set retries count.
     *
     * @param int $retriesCount
     * @return EventInterface
     */
    public function setRetriesCount(int $retriesCount): EventInterface;

    /**
     * Returns event info.
     *
     * @return string
     */
    public function getInfo(): string;

    /**
     * Sets event info.
     *
     * @param string $info
     * @return EventInterface
     */
    public function setInfo(string $info): EventInterface;

    /**
     * Returns event priority.
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Sets event priority
     *
     * @param int $priority
     * @return EventInterface
     */
    public function setPriority(int $priority): EventInterface;
}
