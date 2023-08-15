<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory;

/**
 * For the given Customer Segment rule condition, supplies the contents that go into the table used to determine
 * which cart sales rules should be further evaluated.
 */
class Segment implements FilterableConditionInterface
{
    const FILTER_TEXT_PREFIX = 'customer:segment:';
    const FILTER_TEXT_GENERATOR_CLASS =
        \Magento\CustomerSegment\Model\Segment\Condition\FilterTextGenerator\Segment::class;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var string[]
     */
    protected $segmentIds;

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
        $this->operator = $data['operator'];
        // split the values into an array of elements.  The values can be a list that is separated by a comma: '1, 2'
        $this->segmentIds = explode(',', str_replace(' ', '', $data['values']));
    }

    /**
     * Whether this condition can be filtered using index table
     *
     * @return bool
     */
    public function isFilterable()
    {
        return $this->operator == '()'
        || $this->operator == '=='
        || $this->operator == '!='
        || $this->operator == '!()';
    }

    /**
     * Return a list of filter groups that represent this condition
     *
     * @return FilterGroupInterface[]
     */
    public function getFilterGroups()
    {
        if ($this->filterGroups === null) {
            $weight = 1;

            $negativeFilters = [];
            $negativeCondition = false;
            if ($this->operator == '!=' || $this->operator == '!()') {
                $negativeCondition = true;
                $weight = -1;
            }

            $this->filterGroups = [];
            foreach ($this->segmentIds as $segmentId) {
                /** @var FilterInterface $filter */
                $filter = $this->filterHelper->createFilter();
                $filter->setFilterText(self::FILTER_TEXT_PREFIX . $segmentId)
                    ->setWeight($weight)
                    ->setFilterTextGeneratorClass(self::FILTER_TEXT_GENERATOR_CLASS)
                    ->setFilterTextGeneratorArguments(json_encode([]));

                if ($negativeCondition) {
                    // we will accumulate all negative filters into one group (logical 'and')
                    $negativeFilters[] = $filter;
                } else {
                    // we accumulate all positive filters into separate groups (logical 'or')
                    /** @var FilterGroupInterface $filterGroup */
                    $filterGroup = $this->filterGroupFactory->create();
                    $filterGroup->setFilters([$filter]);
                    $this->filterGroups[] = $filterGroup;
                }
            }

            if ($negativeCondition && !empty($negativeFilters)) {
                // add in the 'true' filter (which has a +1 weight).
                // This will cause the rule to be a candidate only if none of the negative conditions are met.
                $negativeFilters[] = $this->filterHelper->getFilterTrue();

                /** @var FilterGroupInterface $filterGroup */
                $filterGroup = $this->filterGroupFactory->create();
                $filterGroup->setFilters($negativeFilters);
                $this->filterGroups[] = $filterGroup;
            }
        }

        return $this->filterGroups;
    }
}
