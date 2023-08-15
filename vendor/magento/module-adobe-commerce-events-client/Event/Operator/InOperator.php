<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Operator;

/**
 * Verifies that event data value is in the list of provided values.
 */
class InOperator implements OperatorInterface
{
    /**
     * Verifies that event data value is in the list of provided values.
     *
     * @param string $ruleValue
     * @param mixed $fieldValue
     * @return bool
     */
    public function verify(string $ruleValue, $fieldValue): bool
    {
        return in_array($fieldValue, explode(',', $ruleValue));
    }
}
