<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Condition\Combine;

use Magento\Framework\DB\Select;

/**
 * Abstract class for combine rule condition
 */
abstract class AbstractCombine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * Rule Resource
     *
     * @var \Magento\Reminder\Model\ResourceModel\Rule
     */
    protected $_ruleResource;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_ruleResource = $ruleResource;
    }

    /**
     * Customize default operator input by type mapper for some types
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['numeric'] = ['==', '!=', '>=', '>', '<=', '<'];
            $this->_defaultOperatorInputByType['string'] = ['==', '!=', '{}', '!{}'];
        }
        return $this->_defaultOperatorInputByType;
    }

    /**
     * Add operator when loading array
     *
     * @param array $arr
     * @param string $key
     * @return $this
     */
    public function loadArray($arr, $key = 'conditions')
    {
        if (isset($arr['operator'])) {
            $this->setOperator($arr['operator']);
        }

        if (isset($arr['attribute'])) {
            $this->setAttribute($arr['attribute']);
        }

        return parent::loadArray($arr, $key);
    }

    /**
     * Get condition combine resource model
     *
     * @return \Magento\Reminder\Model\ResourceModel\Rule
     */
    public function getResource()
    {
        return $this->_ruleResource;
    }

    /**
     * Get filter by customer condition for rule matching sql
     *
     * @param null|int|\Zend_Db_Expr $customer
     * @param string $fieldName
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _createCustomerFilter($customer, $fieldName)
    {
        return "{$fieldName} = root.entity_id";
    }

    /**
     * Build query for matching customer to rule condition
     *
     * @param null|int|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _prepareConditionsSql($customer, $website)
    {
        $select = $this->getResource()->createSelect();
        $table = $this->getResource()->getTable('customer_entity');
        $select->from($table, [new \Zend_Db_Expr(1)]);
        $select->where($this->_createCustomerFilter($customer, 'entity_id'));
        return $select;
    }

    /**
     * Check if condition is required. It affect condition select result comparison type (= || <>)
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    protected function _getRequiredValidation()
    {
        return $this->getValue() == 1;
    }

    /**
     * Get SQL select for matching customer to rule condition
     *
     * @param null|int|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @return Select
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getConditionsSql($customer, $website)
    {
        /**
         * Build base SQL
         */
        $select = $this->_prepareConditionsSql($customer, $website);
        $required = $this->_getRequiredValidation();
        $whereFunction = $this->getAggregator() == 'all' ? 'where' : 'orWhere';
        $operator = $required ? '=' : '<>';
        //$operator       = '=';

        $gotConditions = false;

        /**
         * Add children sub-selects conditions
         */
        foreach ($this->getConditions() as $condition) {
            if ($sql = $condition->getConditionsSql($customer, $website)) {
                $criteriaSql = "(" . $select->getConnection()->getIfNullSql("(" . $sql . ")", 0) . " {$operator} 1)";
                $select->{$whereFunction}($criteriaSql);
                $gotConditions = true;
            }
        }

        /**
         * Process combine sub-filters. Sub-filters are part of base select which can be affected by children.
         */
        $subfilterMap = $this->_getSubfilterMap();
        if ($subfilterMap) {
            foreach ($this->getConditions() as $condition) {
                $subfilterType = $condition->getSubfilterType();
                if (isset($subfilterMap[$subfilterType])) {
                    $subfilter = $condition->getSubfilterSql($subfilterMap[$subfilterType], $required, $website);
                    if ($subfilter) {
                        $select->{$whereFunction}($subfilter);
                        $gotConditions = true;
                    }
                }
            }
        }

        if (!$gotConditions) {
            $select->where('1=1');
        }

        return $select;
    }

    /**
     * Get information about sub-filters map.
     *
     * Map contain children condition type and associated column name from itself select.
     * Example: array('my_subtype'=>'my_table.my_column')
     * In practice - date range can be as sub-filter for different types of condition combines.
     * Logic of this filter apply is same - but column names different
     *
     * @return array
     */
    protected function _getSubfilterMap()
    {
        return [];
    }

    /**
     * Limit select by website with joining to store table
     *
     * @param \Magento\Framework\DB\Select $select
     * @param int|\Zend_Db_Expr $website
     * @param string $storeIdField
     * @return $this
     */
    protected function _limitByStoreWebsite(\Magento\Framework\DB\Select $select, $website, $storeIdField)
    {
        $storeTable = $this->getResource()->getTable('store');
        $select->join(
            ['store' => $storeTable],
            $storeIdField . '=store.store_id',
            []
        )->where(
            'store.website_id=?',
            $website
        );
        return $this;
    }

    /**
     * Get satisfied IDs
     *
     * @param int $websiteId
     * @return array
     */
    public function getSatisfiedIds($websiteId)
    {
        $checkAll = $this->getAggregator() == 'all';
        $allMustBeTrue = $this->_getRequiredValidation();
        $result = [];
        $count = 0;

        // Conditions must evaluate to false
        foreach ($this->getConditions() as $condition) {
            if ($allMustBeTrue) {
                // customer IDs that match child condition
                $customerIds = $condition->getSatisfiedIds($websiteId);
            } else {
                // customer IDs that do not match child condition
                $customerIds = $this->getCustomerIds($websiteId, $condition->getSatisfiedIds($websiteId));
            }

            if ($checkAll) {
                if ($count > 0) {
                    $result = array_intersect($customerIds, $result);
                } else {
                    $result = $customerIds;
                }
                if (empty($result)) {
                    return [];
                }
            } else {
                // phpcs:ignore
                $result = array_merge($customerIds, $result);
            }
            $count++;
        }

        //case when we don't have any condition
        if ($count == 0) {
            $result = $this->getResource()
                ->getConnection()
                ->fetchCol($this->getConditionsSql(null, $websiteId));
        }

        return array_unique($result);
    }

    /**
     * Retrieve the list of customer IDs from given website
     *
     * @param int $websiteId target website ID
     * @param array $excludedIds customer IDs that do not match criteria
     * @return array
     */
    protected function getCustomerIds($websiteId, array $excludedIds)
    {
        $connection = $this->getResource()->getConnection();
        $select = $connection->select();
        $select->from(['customer' => $this->getResource()->getTable('customer_entity')], ['entity_id'])
            ->where('customer.website_id = ?', $websiteId);
        if (!empty($excludedIds)) {
            $select->where('customer.entity_id NOT IN(?)', $excludedIds);
        }

        return $connection->fetchCol($select);
    }

    /**
     * Get assigned to website store ids
     *
     * @TODO move this method to rule module
     * @param int $websiteId
     * @return array
     */
    protected function getStoreByWebsite($websiteId)
    {
        $storeTable = $this->getResource()->getTable('store');
        $storeSelect = $this->getResource()->createSelect()->from($storeTable, ['store_id'])->where(
            'website_id=?',
            $websiteId
        );
        $data = $this->getResource()->getConnection()->fetchCol($storeSelect);
        return $data;
    }
}
