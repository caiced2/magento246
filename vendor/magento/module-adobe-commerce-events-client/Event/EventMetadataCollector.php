<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataException;
use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataPool;

/**
 * Loads and returns metadata to send in the message to the event pipeline
 */
class EventMetadataCollector
{
    /**
     * @var EventMetadataPool
     */
    private EventMetadataPool $metadataPool;

    /**
     * @var array
     */
    private array $metadata = [];

    /**
     * @param EventMetadataPool $metadataPool
     */
    public function __construct(
        EventMetadataPool $metadataPool
    ) {
        $this->metadataPool = $metadataPool;
    }

    /**
     * Loads and returns metadata to send in the message to the event pipeline
     *
     * @return string[]
     * @throws EventMetadataException
     */
    public function getMetadata(): array
    {
        if (empty($this->metadata)) {
            $metadata = [];

            foreach ($this->metadataPool->getAll() as $metadataObject) {
                $metadata[] = $metadataObject->get();
            }

            $this->metadata = array_merge(...$metadata);
        }

        return $this->metadata;
    }
}
