<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Queue\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Adds information to the message queue about publishing event data.
 */
class EventPublisher
{
    private const TOPIC_EVENTING_PUBLISHER = 'commerce.eventing.event.publish';

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @param PublisherInterface $publisher
     */
    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Adds information to the message queue about publishing event data.
     *
     * @param string $event
     */
    public function execute(string $event): void
    {
        $this->publisher->publish(
            self::TOPIC_EVENTING_PUBLISHER,
            $event
        );
    }
}
