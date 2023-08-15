<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for @see \Magento\AdobeCommerceEventsClient\Event\Event
 */
class EventFactory
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
     * @param array $data
     * @return Event
     */
    public function create(array $data = []): Event
    {
        return $this->objectManager->create(Event::class, $data);
    }
}
