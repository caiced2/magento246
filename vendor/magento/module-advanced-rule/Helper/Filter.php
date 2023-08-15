<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedRule\Helper;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterfaceFactory;
use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\FilterInterfaceFactory;
use Magento\Framework\App\Helper\Context;

class Filter extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var FilterInterfaceFactory
     */
    protected $filterInterfaceFactory;

    /**
     * @var FilterGroupInterfaceFactory
     */
    protected $filterGroupInterfaceFactory;

    /**
     * @param Context $context
     * @param FilterInterfaceFactory $filterInterfaceFactory
     * @param FilterGroupInterfaceFactory $filterGroupInterfaceFactory
     */
    public function __construct(
        Context $context,
        FilterInterfaceFactory $filterInterfaceFactory,
        FilterGroupInterfaceFactory $filterGroupInterfaceFactory
    ) {
        parent::__construct($context);
        $this->filterInterfaceFactory = $filterInterfaceFactory;
        $this->filterGroupInterfaceFactory = $filterGroupInterfaceFactory;
    }

    /**
     * Combine two filter groups arrays using logical and.
     *
     * @param array $filterGroups1
     * @param array $filterGroups2
     * @return array
     */
    public function logicalAndFilterGroupArray(array $filterGroups1, array $filterGroups2)
    {
        $combinedFilterGroups = [];
        foreach ($filterGroups1 as $filterGroup1) {
            foreach ($filterGroups2 as $filterGroup2) {
                $combinedFilterGroups[] = $this->logicalAndFilterGroup($filterGroup1, $filterGroup2);
            }
        }
        return $combinedFilterGroups;
    }

    /**
     * Combine two filter groups using logical and.
     *
     * @param FilterGroupInterface $filterGroup1
     * @param FilterGroupInterface $filterGroup2
     * @return FilterGroupInterface
     */
    public function logicalAndFilterGroup(FilterGroupInterface $filterGroup1, FilterGroupInterface $filterGroup2)
    {
        /** @var FilterInterface[] $filters */
        $filters = [];
        /** @var FilterInterface[] $combined */
        $combined = array_merge($filterGroup1->getFilters(), $filterGroup2->getFilters());
        foreach ($combined as $filter) {
            $text = $filter->getFilterText();
            $newFilter = $this->copyFilter($filter);
            if (isset($filters[$text]) && $filters[$text]->getWeight() * $newFilter->getWeight() < 0) {
                //conflicting conditions, will never match
                return $this->getFilterGroupFalse();
            } else {
                //identical conditions, only one is needed
                $filters[$text] = $newFilter;
            }
        }

        $positiveFilters = array_filter(
            $filters,
            function ($filter) {
                return $filter->getWeight() > 0;
            }
        );
        $count = count($positiveFilters);
        foreach ($positiveFilters as $filter) {
            $filter->setWeight(1 / $count);
        }

        /** @var FilterGroupInterface $filterGroup */
        $filterGroup = $this->filterGroupInterfaceFactory->create();
        $filterGroup->setFilters($filters);
        return $filterGroup;
    }

    /**
     * Return a filter group that will always fail to match
     *
     * @return FilterGroupInterface
     */
    public function getFilterGroupFalse()
    {
        $filterGroup = $this->filterGroupInterfaceFactory->create();
        $filterGroup->setFilters([$this->getFilterFalse()]);
        return $filterGroup;
    }

    /**
     * @param FilterInterface $filter
     * @return FilterGroupInterface
     */
    public function negateFilter(FilterInterface $filter)
    {
        /** @var FilterGroupInterface $filterGroup */
        $filterGroup = $this->filterGroupInterfaceFactory->create();
        $filterTrue = $this->getFilterTrue();
        $newFilter = $this->copyFilter($filter);
        $newFilter->setWeight($newFilter->getWeight() * -1);
        $filterGroup->setFilters([$newFilter, $filterTrue]);
        return $filterGroup;
    }

    /**
     * @return FilterInterface
     */
    public function getFilterTrue()
    {
        /** @var FilterInterface $filterTrue */
        $filterTrue = $this->createFilter();
        $filterTrue->setWeight(1);
        $filterTrue->setFilterText(FilterInterface::FILTER_TEXT_TRUE);
        return $filterTrue;
    }

    /**
     * @return FilterInterface
     */
    public function getFilterFalse()
    {
        /** @var FilterInterface $filterFalse */
        $filterFalse = $this->createFilter();
        $filterFalse->setWeight(-1);
        $filterFalse->setFilterText(FilterInterface::FILTER_TEXT_TRUE);
        return $filterFalse;
    }

    /**
     * @param FilterInterface $filter
     * @return FilterInterface
     */
    public function copyFilter(FilterInterface $filter)
    {
        /** @var FilterInterface $newFilter */
        $newFilter = $this->createFilter();
        $newFilter->setFilterText($filter->getFilterText());
        $newFilter->setFilterTextGeneratorClass($filter->getFilterTextGeneratorClass());
        $newFilter->setFilterTextGeneratorArguments($filter->getFilterTextGeneratorArguments());
        $newFilter->setWeight($filter->getWeight());
        return $newFilter;
    }

    /**
     * @return FilterInterface
     */
    public function createFilter()
    {
        return $this->filterInterfaceFactory->create();
    }
}
