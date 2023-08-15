<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Rule;

/**
 * Rule data object
 */
class Rule implements RuleInterface
{
    /**
     * @var string
     */
    private string $field;

    /**
     * @var string
     */
    private string $operator;

    /**
     * @var string
     */
    private string $value;

    /**
     * @param string $field
     * @param string $operator
     * @param string $value
     */
    public function __construct(string $field, string $operator, string $value)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @inheritDoc
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
