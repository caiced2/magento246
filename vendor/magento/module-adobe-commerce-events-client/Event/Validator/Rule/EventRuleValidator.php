<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Validator\Rule;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorFactory;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * Validator of rules for a provided event
 */
class EventRuleValidator implements EventValidatorInterface
{
    /**
     * @var OperatorFactory
     */
    private OperatorFactory $operatorFactory;

    /**
     * @param OperatorFactory $operatorFactory
     */
    public function __construct(OperatorFactory $operatorFactory)
    {
        $this->operatorFactory = $operatorFactory;
    }

    /**
     * Validates that all rules for an input event are defined using valid operator names.
     *
     * @param Event $event
     * @param bool $force
     * @return void
     * @throws ValidatorException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(Event $event, bool $force = false): void
    {
        $validOperators = $this->operatorFactory->getOperatorNames();

        foreach ($event->getRules() as $rule) {
            if (!in_array($rule[RuleInterface::RULE_OPERATOR], $validOperators)) {
                throw new ValidatorException(
                    __('"%1" is an invalid event rule operator name', $rule[RuleInterface::RULE_OPERATOR])
                );
            }
        }
    }
}
