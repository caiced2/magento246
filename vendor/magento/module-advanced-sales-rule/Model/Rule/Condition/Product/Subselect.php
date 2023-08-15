<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\Product;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;

class Subselect extends \Magento\SalesRule\Model\Rule\Condition\Product\Subselect implements
    \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
{
    /**
     * @var \Magento\AdvancedRule\Helper\CombineCondition
     */
    protected $conditionHelper;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\SalesRule\Model\Rule\Condition\Product $ruleConditionProduct
     * @param \Magento\AdvancedRule\Helper\CombineCondition $conditionHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\SalesRule\Model\Rule\Condition\Product $ruleConditionProduct,
        \Magento\AdvancedRule\Helper\CombineCondition $conditionHelper,
        array $data = []
    ) {
        parent::__construct($context, $ruleConditionProduct, $data);
        $this->conditionHelper = $conditionHelper;
    }

    /**
     * Whether this condition can be filtered using index table
     *
     * @return bool
     */
    public function isFilterable()
    {
        $conditionRequiresProductFound = $this->isConditionRequireProductFound();
        if (!$conditionRequiresProductFound) {
            return false;
        }

        $aggregator = $this->getAggregator();

        if ($aggregator == 'all') {
            return $this->conditionHelper->hasFilterableCondition($this->getConditions());
        } else {
            return !$this->conditionHelper->hasNonFilterableCondition($this->getConditions());
        }
    }

    /**
     * Check whether the condition requires products being found
     *
     * @return bool
     */
    protected function isConditionRequireProductFound()
    {
        $operator = $this->getOperator();
        $value = (float)$this->getValueParsed();

        if ($value > 0 && ($operator == '>' || $operator == '>=')) {
            return true;
        }
        return false;
    }

    /**
     * Return a list of filter groups that represent this condition
     *
     * @return FilterGroupInterface[]
     */
    public function getFilterGroups()
    {
        $aggregator = $this->getAggregator();

        if ($aggregator == 'all') {
            return $this->conditionHelper->logicalAndConditions($this->getConditions());
        } else {
            return $this->conditionHelper->logicalOrConditions($this->getConditions());
        }
    }
}
