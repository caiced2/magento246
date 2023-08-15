<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition;

use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\Filter as FilterModel;
use Magento\Quote\Model\Quote\Address;

class Filter extends \Magento\Rule\Model\ResourceModel\AbstractResource
{
    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_salesrule_filter', null);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFilterTextGenerators()
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(),
            [
                FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS,
                FilterModel::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS

            ]
        )->where(
            FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS . ' IS NOT NULL'
        )->group(
            [
                FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS,
                FilterModel::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS
            ]
        );
        return $connection->fetchAll($select);
    }

    /**
     * @param array $filterText
     * @return array
     */
    public function filterRules(array $filterText)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(),
            ['rule_id']
        )->where(
            $connection->quoteInto(
                FilterModel::KEY_FILTER_TEXT . ' IN (?) ',
                $filterText
            )
        )->group(
            ['group_id', 'rule_id']
        )->having(
            'sum(weight) > 0.999'
        );
        $results = $connection->fetchAssoc($select);
        return array_keys($results);
    }

    /**
     * @param int[] $ruleIdArray
     * @return bool
     */
    public function deleteRuleFilters($ruleIdArray = [])
    {
        if (is_array($ruleIdArray)) {
            $this->getConnection()->delete(
                $this->getMainTable(),
                empty($ruleIdArray) ? [] : ['rule_id IN (?)' => $ruleIdArray]
            );
            return true;
        }
        return false;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function insertFilters($data)
    {
        if (is_array($data)) {
            $this->getConnection()->insertMultiple($this->getMainTable(), $data);
            return true;
        }
        return false;
    }
}
