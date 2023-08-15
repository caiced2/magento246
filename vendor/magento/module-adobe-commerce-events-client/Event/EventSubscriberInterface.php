<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * Interface for event subscribing/unsubscribing
 *
 * @api
 * @since 1.1.0
 */
interface EventSubscriberInterface
{
    public const IO_EVENTS_CONFIG_NAME = 'io_events';
    public const EVENT_PREFIX_COMMERCE = 'com.adobe.commerce.';

    public const EVENT_TYPE_PLUGIN = 'plugin';
    public const EVENT_TYPE_OBSERVER = 'observer';
    public const EVENT_TYPES = [self::EVENT_TYPE_PLUGIN, self::EVENT_TYPE_OBSERVER];

    /**
     * Subscribes to the event.
     *
     * @param Event $event
     * @param bool $force
     * @return void
     * @throws ValidatorException
     */
    public function subscribe(Event $event, bool $force = false): void;

    /**
     * Unsubscribing from the event.
     *
     * @param Event $event
     * @return void
     * @throws ValidatorException
     */
    public function unsubscribe(Event $event): void;
}
