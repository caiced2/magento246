<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory;

abstract class AbstractFilterableCondition implements FilterableConditionInterface
{
    const FILTER_TEXT_PREFIX = 'quote_address:';

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var mixed
     */
    protected $attributeValue;

    /**
     * @var \Magento\Rule\Model\Condition\AbstractCondition
     */
    protected $condition;

    /**
     * @var \Magento\AdvancedRule\Helper\Filter
     */
    protected $filterHelper;

    /**
     * @var FilterGroupInterfaceFactory
     */
    protected $filterGroupFactory;

    /**
     * @var FilterGroupInterface
     */
    protected $filterGroups;

    /**
     * @param FilterGroupInterfaceFactory $filterGroupFactory
     * @param \Magento\AdvancedRule\Helper\Filter $filterHelper
     * @param \Magento\Rule\Model\Condition\AbstractCondition $condition
     */
    public function __construct(
        FilterGroupInterfaceFactory $filterGroupFactory,
        \Magento\AdvancedRule\Helper\Filter $filterHelper,
        \Magento\Rule\Model\Condition\AbstractCondition $condition
    ) {
        $this->filterGroupFactory = $filterGroupFactory;
        $this->filterHelper = $filterHelper;
        $this->condition = $condition;
        $this->attribute = $this->condition->getAttribute();
        $this->attributeValue = $this->condition->getValueParsed();
        $this->operator = $this->condition->getOperator();
    }

    /**
     * Whether this condition can be filtered using index table
     *
     * @return bool
     */
    public function isFilterable()
    {
        return $this->isOperatorFilterable() && $this->isAttributeTypeFilterable();
    }

    /**
     * @return bool
     */
    protected function isOperatorFilterable()
    {
        return $this->operator == '==' || $this->operator == '!=';
    }

    /**
     * Determine whether condition is filterable based on attribute value type
     *
     * @return bool
     */
    protected function isAttributeTypeFilterable()
    {
        $value = $this->condition->getValueParsed();
        return is_scalar($value);
    }

    /**
     * Return a list of filter groups that represent this condition
     *
     * @return FilterGroupInterface[]
     */
    public function getFilterGroups()
    {
        if ($this->filterGroups === null) {
            $negativeCondition = false;
            if ($this->operator == '!=') {
                $negativeCondition = true;
            }
            $this->filterGroups = [];
            /** @var FilterInterface $filter */
            $filter = $this->filterHelper->createFilter();
            $filter->setFilterText($this->getFilterTextPrefix() . $this->attribute . ':' . $this->attributeValue)
                ->setWeight(1)
                ->setFilterTextGeneratorClass($this->getFilterTextGeneratorClass())
                ->setFilterTextGeneratorArguments(json_encode(['attribute' => $this->attribute]));
            if ($negativeCondition) {
                $filterGroup = $this->filterHelper->negateFilter($filter);
            } else {
                /** @var FilterGroupInterface $filterGroup */
                $filterGroup = $this->filterGroupFactory->create();
                $filterGroup->setFilters([$filter]);
            }
            $this->filterGroups[] = $filterGroup;
        }

        return $this->filterGroups;
    }

    /**
     * @return string
     */
    abstract protected function getFilterTextPrefix();

    /**
     * @return string
     */
    abstract protected function getFilterTextGeneratorClass();
}
