<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

use Magento\Framework\DataObject;

/**
 * Event data object
 */
class EventData extends DataObject
{
    public const EVENT_NAME = 'event_name';
    public const EVENT_CLASS_EMITTER = 'event_class_emitter';

    /**
     * Returns event name
     *
     * @return string
     */
    public function getEventName(): string
    {
        return (string)$this->getData(self::EVENT_NAME);
    }

    /**
     * Returns class name where event is emitted
     *
     * @return string
     */
    public function getEventClassEmitter(): string
    {
        return (string)$this->getData(self::EVENT_CLASS_EMITTER);
    }
}
