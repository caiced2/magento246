<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Operator;

use Throwable;

/**
 * Verifies that event data value is matching regex pattern from the rule.
 */
class RegexOperator implements OperatorInterface
{
    /**
     * Verifies that event data value is matching regex pattern from the rule.
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

        try {
            return preg_match($ruleValue, (string)$fieldValue) === 1;
        } catch (Throwable $e) {
            throw new OperatorException(__('Regex operation failed: %1', $e->getMessage()));
        }
    }
}
