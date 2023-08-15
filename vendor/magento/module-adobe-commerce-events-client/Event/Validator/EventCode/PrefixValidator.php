<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Validator\EventCode;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * Validates that event code has correct prefix
 */
class PrefixValidator implements EventValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(Event $event, bool $force = false): void
    {
        $this->validateEventCode($event->getName());
        if (!empty($event->getParent())) {
            $this->validateEventCode($event->getParent());
        }
    }

    /**
     * Validates that the input event code contains a valid prefix.
     *
     * @param string $eventCode
     * @return void
     * @throws ValidatorException
     */
    private function validateEventCode(string $eventCode) : void
    {
        $eventCodeParts = explode('.', $eventCode, 2);
        if (count($eventCodeParts) === 1) {
            throw new ValidatorException(
                __(
                    'Event code must consist of a type label and an event code separated by a dot: '
                    . '"<type>.<event_code>". Valid types: %1',
                    implode(', ', EventSubscriberInterface::EVENT_TYPES)
                )
            );
        }

        $prefix = $eventCodeParts[0];
        if (!in_array($prefix, EventSubscriberInterface::EVENT_TYPES)) {
            throw new ValidatorException(
                __(
                    'Invalid event type "%1". Valid types: %2',
                    $prefix,
                    implode(', ', EventSubscriberInterface::EVENT_TYPES)
                )
            );
        }
    }
}
