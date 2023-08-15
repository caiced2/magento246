<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data;

use JsonSerializable;
use Magento\AdobeIoEventsClient\Api\EventMetadataInterface;
use Magento\Framework\DataObject;

/**
 * Event metadata data object
 *
 * @api
 * @since 1.1.0
 */
class EventMetadata extends DataObject implements JsonSerializable, EventMetadataInterface
{
    private const EVENT_CODE = "event_code";
    private const DESCRIPTION = "description";
    private const LABEL = "label";

    /**
     * Serialize to JSON
     *
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            self::EVENT_CODE => $this->getEventCode(),
            self::LABEL => $this->getLabel(),
            self::DESCRIPTION => $this->getDescription()
        ];
    }

    /**
     * Return Event Code
     *
     * @return string
     */
    public function getEventCode(): string
    {
        return (string) $this->getData(self::EVENT_CODE);
    }

    /**
     * Return Description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return (string) $this->getData(self::DESCRIPTION);
    }

    /**
     * Represent as String
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf("%s (%s) %s", $this->getLabel(), $this->getEventCode(), $this->getDescription());
    }

    /**
     * Return Label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return (string) $this->getData(self::LABEL);
    }
}
