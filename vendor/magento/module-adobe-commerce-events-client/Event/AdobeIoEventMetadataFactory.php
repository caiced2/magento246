<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeIoEventsClient\Api\EventMetadataInterface;
use Magento\AdobeIoEventsClient\Model\Data\EventMetadataFactory;

/**
 * Generates metadata for given event code
 */
class AdobeIoEventMetadataFactory
{
    /**
     * @var EventMetadataFactory
     */
    private EventMetadataFactory $eventMetadataFactory;

    /**
     * @param EventMetadataFactory $eventMetadataFactory
     */
    public function __construct(EventMetadataFactory $eventMetadataFactory)
    {
        $this->eventMetadataFactory = $eventMetadataFactory;
    }

    /**
     * Generates metadata info base on event type.
     *
     * @param string $eventCode
     * @return EventMetadataInterface
     */
    public function generate(string $eventCode): EventMetadataInterface
    {
        $data = [
            'event_code' => $eventCode,
            'description' => 'event ' . $eventCode,
            'label' => 'event' . $eventCode
        ];

        $eventCodeParts = explode('.', str_replace(EventSubscriberInterface::EVENT_PREFIX_COMMERCE, '', $eventCode), 2);

        if ($eventCodeParts[0] === EventSubscriberInterface::EVENT_TYPE_PLUGIN) {
            $data['description'] = 'Plugin event ' . $eventCodeParts[1];
            $data['label'] = 'Plugin event ' . $eventCodeParts[1];
        } elseif ($eventCodeParts[0] === EventSubscriberInterface::EVENT_TYPE_OBSERVER) {
            $data['label'] = 'Observer event ' . $eventCodeParts[1];
            $data['description'] = 'Observer event ' . $eventCodeParts[1];
        }

        return $this->eventMetadataFactory->create(['data' => $data]);
    }
}
