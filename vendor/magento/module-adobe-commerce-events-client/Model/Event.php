<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Model;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event as ResourceModel;
use JsonException;

/**
 * @inheritDoc
 */
class Event extends AbstractModel implements EventInterface, IdentityInterface
{
    private const CACHE_TAG = 'event_data';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @inheritDoc
     */
    public function getId(): ?string
    {
        return parent::getData(self::FIELD_ID);
    }

    /**
     * @inheritDoc
     */
    public function getEventCode(): ?string
    {
        return parent::getData(self::FIELD_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setEventCode(string $eventCode): EventInterface
    {
        $this->setData(self::FIELD_CODE, $eventCode);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEventData(): array
    {
        if ($data = $this->getData(self::FIELD_DATA)) {
            try {
                return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new EventException(__('Cannot decode data'), $exception);
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function setEventData(array $eventData): EventInterface
    {
        try {
            $this->setData(self::FIELD_DATA, json_encode($eventData, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new EventException(__('Cannot encode data'), $exception);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(): array
    {
        if ($metadata = $this->getData(self::FIELD_METADATA)) {
            try {
                return json_decode($metadata, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new EventException(__('Cannot decode message metadata'), $exception);
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function setMetadata(array $metadata): EventInterface
    {
        try {
            $this->setData(self::FIELD_METADATA, json_encode($metadata, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new EventException(__('Cannot encode message metadata'), $exception);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(int $statusCode): EventInterface
    {
        $this->setData(self::FIELD_STATUS, $statusCode);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRetriesCount(): int
    {
        return (int)$this->getData(self::FIELD_RETRIES);
    }

    /**
     * @inheritDoc
     */
    public function setRetriesCount(int $retriesCount): EventInterface
    {
        $this->setData(self::FIELD_RETRIES, $retriesCount);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInfo(): string
    {
        return $this->getData(self::FIELD_INFO);
    }

    /**
     * @inheritDoc
     */
    public function setInfo(string $info): EventInterface
    {
        $this->setData(self::FIELD_INFO, $info);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return (int)$this->getData(self::FIELD_PRIORITY);
    }

    /**
     * @inheritDoc
     */
    public function setPriority(int $priority): EventInterface
    {
        $this->setData(self::FIELD_PRIORITY, $priority);

        return $this;
    }
}
