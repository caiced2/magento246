<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product;

class Attribute extends \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\AbstractFilterableCondition
{
    const FILTER_TEXT_PREFIX = 'product:attribute:';
    const FILTER_TEXT_GENERATOR_CLASS =
        \Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute::class;

    /**
     * Override default behavior to return true only if the operator is '=='
     * This is needed because we will aggregate multiple products when querying the filter table and we
     * can not handle the negative case when there are multiple products
     *
     * @return bool
     */
    protected function isOperatorFilterable()
    {
        return $this->operator == '==';
    }

    /**
     * @return string
     */
    protected function getFilterTextPrefix()
    {
        return self::FILTER_TEXT_PREFIX;
    }

    /**
     * @return string
     */
    protected function getFilterTextGeneratorClass()
    {
        return self::FILTER_TEXT_GENERATOR_CLASS;
    }
}
