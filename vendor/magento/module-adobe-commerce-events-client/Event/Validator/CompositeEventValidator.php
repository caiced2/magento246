<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Validator;

use Magento\AdobeCommerceEventsClient\Event\Event;

/**
 * Composite validator of provided event
 */
class CompositeEventValidator implements EventValidatorInterface
{
    /**
     * @var EventValidatorInterface[]
     */
    private array $validators;

    /**
     * @param array $validators
     */
    public function __construct(array $validators)
    {
        $this->validators = $validators;
    }

    /**
     * @inheritDoc
     */
    public function validate(Event $event, bool $force = false): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($event, $force);
        }
    }
}
