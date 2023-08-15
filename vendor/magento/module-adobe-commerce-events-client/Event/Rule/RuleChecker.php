<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Rule;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorFactory;

/**
 * Checks if event data passed a list of rules, do nothing if event does not have configured rules.
 */
class RuleChecker
{
    /**
     * @var RuleFactory
     */
    private RuleFactory $ruleFactory;

    /**
     * @var OperatorFactory
     */
    private OperatorFactory $operatorFactory;

    /**
     * @param RuleFactory $ruleFactory
     * @param OperatorFactory $operatorFactory
     */
    public function __construct(RuleFactory $ruleFactory, OperatorFactory $operatorFactory)
    {
        $this->ruleFactory = $ruleFactory;
        $this->operatorFactory = $operatorFactory;
    }

    /**
     * Checks if event data passed a list of rules.
     * Return false if event data does not contain a field that is added in the rule as it is impossible to verify.
     * Return false in the case when any of the rule not verified.
     *
     * @param Event $event
     * @param array $eventData
     * @return bool
     * @throws OperatorException
     */
    public function verify(Event $event, array $eventData): bool
    {
        if (empty($event->getRules())) {
            return true;
        }

        foreach ($event->getRules() as $ruleData) {
            $rule = $this->ruleFactory->create($ruleData);
            $operator = $this->operatorFactory->create($rule->getOperator());

            if (!isset($eventData[$rule->getField()])) {
                return false;
            }

            if (!$operator->verify($rule->getValue(), $eventData[$rule->getField()])) {
                return false;
            }
        }

        return true;
    }
}
