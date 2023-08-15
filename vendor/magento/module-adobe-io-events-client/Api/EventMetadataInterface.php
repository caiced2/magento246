<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Api;

/**
 * Contains basic metadata about an event
 *
 * @api
 * @since 1.1.0
 */
interface EventMetadataInterface
{
    /**
     * Serialize the metadata
     *
     * @return string[]
     */
    public function jsonSerialize(): array;

    /**
     * Return Event Code
     *
     * @return string
     */
    public function getEventCode(): string;

    /**
     * Return Description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Return Label
     *
     * @return string
     */
    public function getLabel(): string;
}
