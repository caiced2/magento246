<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @method \Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine getConditions()
 */
namespace Magento\CustomerSegment\Model\Condition\Combine;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use Magento\Framework\DB\Query\BatchIteratorInterface;

/**
 * Abstract class for customer segment combine rule
 *
 * phpcs:disable Magento2.Classes.AbstractApi
 * @api
 * @since 100.0.2
 */
abstract class AbstractCombine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    protected $_resourceSegment;

    /**
     * @var \Magento\CustomerSegment\Model\ConditionFactory
     */
    protected $_conditionFactory;

    /**
     * @var QueryGenerator
     */
    private $queryGenerator;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param array $data
     * @param QueryGenerator $queryGenerator
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        array $data = [],
        QueryGenerator $queryGenerator = null
    ) {
        $this->_resourceSegment = $resourceSegment;
        parent::__construct($context, $data);
        $this->_conditionFactory = $conditionFactory;
        $this->queryGenerator = $queryGenerator ?: ObjectManager::getInstance()->get(QueryGenerator::class);
    }

    /**
     * Flag of using condition combine (for conditions of Product_Attribute)
     *
     * @var bool
     */
    protected $_combineProductCondition = false;

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return array
     */
    public function getMatchedEvents()
    {
        return [];
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
     * @return \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    public function getResource()
    {
        return $this->_resourceSegment;
    }

    /**
     * Get filter by customer condition for segment matching sql
     *
     * @param Customer|\Zend_Db_Expr $customer
     * @param string $fieldName
     * @return string
     */
    protected function _createCustomerFilter($customer, $fieldName)
    {
        return $customer ? "{$fieldName} = :customer_id" : "{$fieldName} = root.entity_id";
    }

    /**
     * Build query for matching customer to segment condition
     *
     * @param Customer|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return \Magento\Framework\DB\Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $select = $this->getResource()->createSelect();
        $table = $this->getResource()->getTable('customer_entity');
        if ($isFiltered) {
            $select->from($table, [new \Zend_Db_Expr(1)]);
            $select->where($this->_createCustomerFilter($customer, 'entity_id'));
        } else {
            $select->from($table, ['entity_id']);
        }
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
     * Get information if condition is required
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsRequired()
    {
        return $this->_getRequiredValidation();
    }

    /**
     * Get information if it's used as a child of History or List condition
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getCombineProductCondition()
    {
        return $this->_combineProductCondition;
    }

    /**
     * Get SQL select for matching customer to segment condition
     *
     * @param Customer|\Magento\Framework\DB\Select|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param boolean $isFiltered
     * @return \Magento\Framework\DB\Select
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        /**
         * Build base SQL
         */
        $select = $this->_prepareConditionsSql($customer, $website, $isFiltered);
        $required = $this->_getRequiredValidation();
        $aggregator = $this->getAggregator() == 'all' ? ' AND ' : ' OR ';
        $operator = $required ? '=' : '<>';
        $conditions = [];

        /**
         * Add children subselects conditions
         */
        $connection = $this->getResource()->getConnection();
        foreach ($this->getConditions() as $condition) {
            if ($sql = $condition->getConditionsSql($customer, $website, $isFiltered)) {
                $isnull = $connection->getCheckSql($sql, 1, 0);
                if ($condition->getCombineProductCondition()) {
                    $sqlOperator = $condition->getIsRequired() ? '=' : '<>';
                } else {
                    $sqlOperator = $operator;
                }
                $conditions[] = "({$isnull} {$sqlOperator} 1)";
            }
        }
        $conditions = $this->processCombineSubFilters($website, $required, $conditions);

        if (!empty($conditions)) {
            $select->where(implode($aggregator, $conditions));
        }

        return $select;
    }

    /**
     * Check if customer satisfied condition
     *
     * @param int|\Magento\Framework\DataObject $customer
     * @param int $websiteId
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        $checkAll = $this->getAggregator() == 'all';
        foreach ($this->getConditions() as $condition) {
            $result = $condition->isSatisfiedBy($customer, $websiteId, $params);
            if (!$result && $checkAll) {
                return false;
            }
            if ($result && !$checkAll) {
                return true;
            }
        }
        return $checkAll;
    }

    /**
     * Get customers identifiers which satisfied condition
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

        $collectedIds = [];
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
                $collectedIds[] = $customerIds;
            }
            $count++;
        }
        $result = array_merge($result, ...$collectedIds);

        //case when we don't have any condition
        if ($count == 0) {
            $batchSelectIterator = $this->queryGenerator->generate(
                'entity_id',
                $this->getConditionsSql(null, $websiteId, false),
                10000,
                BatchIteratorInterface::UNIQUE_FIELD_ITERATOR
            );

            $values = [];
            foreach ($batchSelectIterator as $select) {
                $values[] = $this->getResource()->getConnection()->fetchCol($select);
            }
            $result = array_merge($result, ...$values);
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
     * Get information about subfilters map. Map contain children condition type and associated
     * column name from itself select.
     * Example: array('my_subtype'=>'my_table.my_column')
     * In practice - date range can be as subfilter for different types of condition combines.
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
        if (is_numeric($website)) {
            $storeSelect = $this->getResource()->createSelect();
            $storeSelect->from(
                ['store' => $storeTable],
                ['store.store_id']
            )->where('store.website_id IN (?)', $website);
            $storeIds = $this->getResource()->getConnection()->fetchCol($storeSelect);
            $select->where($storeIdField . ' IN (?)', $storeIds);
        } else {
            $select->join(
                ['store' => $storeTable],
                $storeIdField . '=store.store_id',
                []
            )->where(
                'store.website_id IN (?)',
                $website
            );
        }
        return $this;
    }

    /**
     * Match parameters
     *
     * @param \Magento\Framework\DB\Select $select
     * @param array $params
     * @return array
     */
    public function matchParameters($select, $params)
    {
        $sql = $select->__toString();
        $result = [];
        if (strpos($sql, ':customer_id') !== false) {
            $result['customer_id'] = $params['customer_id'];
        }
        if (strpos($sql, ':website_id') !== false) {
            $result['website_id'] = $params['website_id'];
        }
        if (strpos($sql, ':quote_id') !== false) {
            $result['website_id'] = $params['quote_id'];
        }
        if (strpos($sql, ':visitor_id') !== false) {
            $result['visitor_id'] = $params['visitor_id'];
        }

        return $result;
    }

    /**
     * Process combine subfilters. Subfilters are part of base select which cah be affected by children.
     *
     * @param int|\Zend_Db_Expr $website
     * @param bool $required
     * @param array $conditions
     * @return array
     */
    protected function processCombineSubFilters($website, $required, array $conditions)
    {
        $subfilterMap = $this->_getSubfilterMap();
        if ($subfilterMap) {
            foreach ($this->getConditions() as $condition) {
                $subfilterType = $condition->getSubfilterType();
                if (isset($subfilterMap[$subfilterType])) {
                    $condition->setCombineProductCondition($this->_combineProductCondition);
                    $subfilter = $condition->getSubfilterSql($subfilterMap[$subfilterType], $required, $website);
                    if ($subfilter) {
                        $conditions[] = $subfilter;
                    }
                }
            }
        }
        return $conditions;
    }
}
