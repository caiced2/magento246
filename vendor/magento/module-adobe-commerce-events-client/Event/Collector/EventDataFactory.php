<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for EventData
 */
class EventDataFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Creates EventData class instance with specified parameters
     *
     * @param array $data
     * @return EventData
     */
    public function create(array $data = []): EventData
    {
        return $this->objectManager->create(EventData::class, ['data' => $data]);
    }
}
