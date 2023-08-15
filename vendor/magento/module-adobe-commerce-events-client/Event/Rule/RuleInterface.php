<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Rule;

/**
 * Interface for rule data object
 *
 * @api
 * @since 1.1.0
 */
interface RuleInterface
{
    public const RULE_FIELD = 'field';
    public const RULE_OPERATOR = 'operator';
    public const RULE_VALUE = 'value';

    /**
     * Returns field name.
     *
     * @return string
     */
    public function getField(): string;

    /**
     * Returns operator name.
     *
     * @return string
     */
    public function getOperator(): string;

    /**
     * Returns rule value.
     *
     * @return string
     */
    public function getValue(): string;
}
