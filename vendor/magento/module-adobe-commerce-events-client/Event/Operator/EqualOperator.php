<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Operator;

/**
 * Verifies that event data value is matching value from the rule.
 */
class EqualOperator implements OperatorInterface
{
    /**
     * Verifies that event data value is matching value from the rule.
     *
     * @param string $ruleValue
     * @param mixed $fieldValue
     * @return bool
     * @throws OperatorException
     */
    public function verify(string $ruleValue, $fieldValue): bool
    {
        if (is_array($fieldValue) || (string)$fieldValue != $fieldValue) {
            throw new OperatorException(__('Input data must be in string format or can be converted to string'));
        }

        return $ruleValue == (string)$fieldValue;
    }
}
