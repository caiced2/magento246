<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedSalesRule\Model\Indexer\SalesRule;

use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\Filter;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;

/**
 * Class AbstractAction
 * @package Magento\AdvancedSalesRule\Model\Indexer\SalesRule
 */
abstract class AbstractAction
{
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    protected $ruleCollection;

    /**
     * @var \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter
     */
    protected $filterResourceModel;

    /**
     * @var int[]
     */
    protected $actionIds = [];

    /**
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $ruleCollection
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter $filterResourceModel
     */
    public function __construct(
        \Magento\SalesRule\Model\ResourceModel\Rule\Collection $ruleCollection,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter $filterResourceModel
    ) {
        $this->ruleCollection = $ruleCollection;
        $this->ruleFactory = $ruleFactory;
        $this->filterResourceModel = $filterResourceModel;
    }

    /**
     * Run full reindex
     * @return $this
     */
    abstract public function execute();

    /**
     * Run reindexation
     * @param bool $fullReindex
     * @return void
     */
    protected function reindex($fullReindex = false)
    {
        if (!empty($this->actionIds)) {
            if ($fullReindex) {
                $this->filterResourceModel->deleteRuleFilters([]);
            } else {
                $this->filterResourceModel->deleteRuleFilters($this->actionIds);
            }
            foreach ($this->actionIds as $actionId) {
                $salesRule = $this->ruleFactory->create()->load($actionId);
                $this->saveFilters($salesRule);
            }
        }
    }

    /**
     * @param int[] $actionIds
     * @return void
     */
    protected function setActionIds($actionIds)
    {
        $this->actionIds = $actionIds;
    }

    /**
     * @param SalesRule $rule
     * @return void
     */
    protected function saveFilters(SalesRule $rule)
    {
        $ruleId = $rule->getId();
        if ($ruleId) {
            $condition = $rule->getConditions();
            $data = [];
            if ($condition instanceof FilterableConditionInterface && $condition->isFilterable()) {
                $filterGroups = $condition->getFilterGroups();
                $groupId = 1;
                foreach ($filterGroups as $filterGroup) {
                    $filters = $filterGroup->getFilters();
                    foreach ($filters as $filter) {
                        $data[] = [
                            'rule_id' => $ruleId,
                            'group_id' => $groupId,
                            'weight' => $filter->getWeight(),
                            Filter::KEY_FILTER_TEXT => $filter->getFilterText(),
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => $filter->getFilterTextGeneratorClass(),
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => $filter->getFilterTextGeneratorArguments(),
                        ];
                    }
                    $groupId++;
                }
            }

            if (empty($data)) {
                $data = $this->getTruePlaceHolder($ruleId);
            }

            $this->filterResourceModel->insertFilters($data);
        }
    }

    /**
     * @param int $ruleId
     * @return array
     */
    protected function getTruePlaceHolder($ruleId)
    {
        return [
            'rule_id' => $ruleId,
            'group_id' => 1,
            'weight' => 1,
            Filter::KEY_FILTER_TEXT => FilterInterface::FILTER_TEXT_TRUE,
            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => null,
            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => null,

        ];
    }
}
