<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Metadata;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Phrase;

/**
 * Pool of event metadata objects
 */
class EventMetadataPool
{
    /**
     * @var EventMetadataInterface[]
     */
    private array $metadata = [];

    /**
     * @param EventMetadataInterface[] $metadata
     * @throws InvalidArgumentException
     */
    public function __construct(array $metadata)
    {
        foreach ($metadata as $metadataObject) {
            if (!$metadataObject instanceof EventMetadataInterface) {
                throw new InvalidArgumentException(
                    new Phrase(
                        'Instance of "%1" is expected, got "%2" instead.',
                        [
                            EventMetadataInterface::class,
                            get_class($metadataObject)
                        ]
                    )
                );
            }
        }
        $this->metadata = $metadata;
    }

    /**
     * Return a list of registered metadata objects.
     *
     * @return EventMetadataInterface[]
     */
    public function getAll(): array
    {
        return $this->metadata;
    }
}
