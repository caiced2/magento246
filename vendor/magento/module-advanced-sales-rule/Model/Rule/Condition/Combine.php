<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;

class Combine extends \Magento\SalesRule\Model\Rule\Condition\Combine implements
    \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
{
    /**
     * @var \Magento\AdvancedRule\Helper\CombineCondition
     */
    protected $conditionHelper;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\SalesRule\Model\Rule\Condition\Address $conditionAddress
     * @param \Magento\AdvancedRule\Helper\CombineCondition $conditionHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\SalesRule\Model\Rule\Condition\Address $conditionAddress,
        \Magento\AdvancedRule\Helper\CombineCondition $conditionHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $eventManager,
            $conditionAddress,
            $data
        );
        $this->conditionHelper = $conditionHelper;
    }

    /**
     * Whether this condition can be filtered using index table
     * Only positive condition is supported right now
     * If the aggregation type is 'all', return true if any condition is filterable
     * Otherwise, return true if all conditions are filterable
     *
     * @return bool
     */
    public function isFilterable()
    {
        $true = (bool)$this->getValue();
        if (!$true) {
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
