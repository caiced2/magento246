<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Queue\Publisher;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for @see EventPublisher
 */
class EventPublisherFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create Event class instance
     *
     * @return EventPublisher
     */
    public function create(): EventPublisher
    {
        return $this->objectManager->get(EventPublisher::class);
    }
}
