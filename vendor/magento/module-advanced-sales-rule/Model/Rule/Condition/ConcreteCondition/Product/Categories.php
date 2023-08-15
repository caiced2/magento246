<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory;

/**
 * Filterable rule condition for product categories.
 */
class Categories implements FilterableConditionInterface
{
    const FILTER_TEXT_PREFIX = 'product:category:';
    const FILTER_TEXT_GENERATOR_CLASS =
        \Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Category::class;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var string[]
     */
    protected $categories;

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
     * @param array $data
     */
    public function __construct(
        FilterGroupInterfaceFactory $filterGroupFactory,
        \Magento\AdvancedRule\Helper\Filter $filterHelper,
        array $data
    ) {
        $this->filterGroupFactory = $filterGroupFactory;
        $this->filterHelper = $filterHelper;
        $this->categories = $data['categories'];
        $this->operator = $data['operator'];
    }

    /**
     * Whether this condition can be filtered using index table
     *
     * @return bool
     */
    public function isFilterable()
    {
        return $this->operator == '()' || $this->operator == '==';
    }

    /**
     * Return a list of filter groups that represent this condition
     *
     * @return FilterGroupInterface[]
     */
    public function getFilterGroups()
    {
        if ($this->filterGroups === null) {
            $this->filterGroups = [];
            if (!$this->isFilterable()) {
                return $this->filterGroups;
            }
            if (!empty($this->categories)) {
                foreach ($this->categories as $category) {
                    /** @var FilterInterface $filter */
                    $filter = $this->filterHelper->createFilter();
                    $filter->setFilterText(self::FILTER_TEXT_PREFIX . $category)
                        ->setWeight(1)
                        ->setFilterTextGeneratorClass(self::FILTER_TEXT_GENERATOR_CLASS)
                        ->setFilterTextGeneratorArguments(json_encode([]));
                    /** @var FilterGroupInterface $filterGroup */
                    $filterGroup = $this->filterGroupFactory->create();
                    $filterGroup->setFilters([$filter]);
                    $this->filterGroups[] = $filterGroup;
                }
            }
        }

        return $this->filterGroups;
    }
}
