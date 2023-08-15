<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Operator;

/**
 * Interface for operator classes
 *
 * @api
 * @since 1.1.0
 */
interface OperatorInterface
{
    /**
     * Verifies that the field value meets the condition.
     *
     * @param string $ruleValue
     * @param mixed $fieldValue
     * @return bool
     * @throws OperatorException
     */
    public function verify(string $ruleValue, $fieldValue): bool;
}
