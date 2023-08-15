<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedRule\Helper;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\Framework\App\Helper\Context;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;

class CombineCondition extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\AdvancedRule\Helper\Filter
     */
    protected $filterHelper;

    /**
     * @param Context $context
     * @param \Magento\AdvancedRule\Helper\Filter $filterHelper
     */
    public function __construct(
        Context $context,
        \Magento\AdvancedRule\Helper\Filter $filterHelper
    ) {
        parent::__construct($context);
        $this->filterHelper = $filterHelper;
    }

    /**
     * Whether any condition is filterable
     *
     * @param \Magento\Rule\Model\Condition\AbstractCondition[] $conditions
     * @return bool
     */
    public function hasFilterableCondition(array $conditions)
    {
        $conditions = $this->getFilterableConditions($conditions);
        $hasFilterableCondition = false;
        foreach ($conditions as $condition) {
            if ($condition->isFilterable()) {
                $hasFilterableCondition = true;
                break;
            }
        }
        return $hasFilterableCondition;
    }

    /**
     * @param \Magento\Rule\Model\Condition\AbstractCondition[] $conditions
     * @return bool
     */
    public function hasNonFilterableCondition(array $conditions)
    {
        $hasNonFilterableCondition = false;
        foreach ($conditions as $condition) {
            if ($condition instanceof \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface) {
                if (!$condition->isFilterable()) {
                    $hasNonFilterableCondition = true;
                    break;
                }
            } else {
                $hasNonFilterableCondition = true;
                break;
            }
        }

        return $hasNonFilterableCondition;
    }

    /**
     * @param \Magento\Rule\Model\Condition\AbstractCondition[] $conditions
     * @return FilterableConditionInterface[]
     */
    public function getFilterableConditions(array $conditions)
    {
        return array_filter(
            $conditions,
            function ($condition) {
                return $condition instanceof \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
            }
        );
    }

    /**
     * @param FilterableConditionInterface[] $conditions
     * @return FilterGroupInterface[]
     */
    public function logicalAndConditions(array $conditions)
    {
        $filterableConditions = $this->getFilterableConditions($conditions);
        $combinedFilterGroups = [];
        foreach ($filterableConditions as $condition) {
            if ($condition->isFilterable()) {
                $filterGroups = $condition->getFilterGroups();
                if (empty($combinedFilterGroups)) {
                    $combinedFilterGroups = $filterGroups;
                } else {
                    $combinedFilterGroups = $this->filterHelper->logicalAndFilterGroupArray(
                        $combinedFilterGroups,
                        $filterGroups
                    );
                }
            }
        }
        return $combinedFilterGroups;
    }

    /**
     * @param FilterableConditionInterface[] $conditions
     * @return FilterableConditionInterface[]
     */
    public function logicalOrConditions(array $conditions)
    {
        $combinedFilterGroups = [];
        foreach ($conditions as $condition) {
            if ($condition->isFilterable()) {
                $filterGroups = $condition->getFilterGroups();
                $combinedFilterGroups = array_merge($combinedFilterGroups, $filterGroups);
            } else {
                return [];
            }
        }
        return $combinedFilterGroups;
    }
}
