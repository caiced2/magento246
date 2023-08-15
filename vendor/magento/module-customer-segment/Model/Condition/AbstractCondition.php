<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Condition;

use Magento\Customer\Model\Customer;

/**
 * Base class to create and process conditions.
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 * @api
 * @since 100.0.2
 */
class AbstractCondition extends \Magento\Rule\Model\Condition\AbstractCondition
{
    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    protected $_resourceSegment;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        array $data = []
    ) {
        $this->_resourceSegment = $resourceSegment;
        parent::__construct($context, $data);
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return string[]
     */
    public function getMatchedEvents()
    {
        return [];
    }

    /**
     * Customize default operator input by type mapper for some types.
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['numeric'] = ['==', '!=', '>=', '>', '<=', '<'];
            $this->_defaultOperatorInputByType['string'] = ['==', '!=', '{}', '!{}'];
            $this->_defaultOperatorInputByType['multiselect'] = ['==', '!=', '[]', '![]'];
        }
        return $this->_defaultOperatorInputByType;
    }

    /**
     * Default operator options getter.
     *
     * Provides all possible operator options.
     *
     * @return array
     */
    public function getDefaultOperatorOptions()
    {
        if (null === $this->_defaultOperatorOptions) {
            $this->_defaultOperatorOptions = parent::getDefaultOperatorOptions();

            $this->_defaultOperatorOptions['[]'] = __('contains');
            $this->_defaultOperatorOptions['![]'] = __('does not contains');
        }
        return $this->_defaultOperatorOptions;
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
     * Generate customer condition string
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
            $storeSelect->from(['store' => $storeTable], ['store.store_id'])
                ->where('store.website_id IN (?)', $website);
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
     * Check that customer matched with website and parameters by condition.
     *
     * @param int $customer
     * @param int $websiteId
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        $result = false;
        $select = $this->getConditionsSql($customer, $websiteId);
        if (isset($select)) {
            $matchedParams = $this->matchParameters($select, $params);
            $result = $this->getResource()->getConnection()->fetchOne($select, $matchedParams);
        }
        return $result > 0;
    }

    /**
     * Return Ids according to condition.
     *
     * @param int $websiteId
     * @return array
     */
    public function getSatisfiedIds($websiteId)
    {
        $result = [];
        $select = $this->getConditionsSql(null, $websiteId, false);
        if (isset($select)) {
            $result = $this->getResource()->getConnection()->fetchCol($select);
        }
        return $result;
    }

    /**
     * Get assigned to website store ids.
     *
     * @param int $websiteId
     * @return array
     */
    public function getStoreByWebsite($websiteId)
    {
        $storeTable = $this->getResource()->getTable('store');
        $storeSelect = $this->getResource()->createSelect()->from($storeTable, ['store_id'])
            ->where('website_id=?', $websiteId);
        $data = $this->getResource()->getConnection()->fetchCol($storeSelect);
        return $data;
    }

    /**
     * Prepare select parameters.
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
            $result['quote_id'] = $params['quote_id'];
        }
        if (strpos($sql, ':visitor_id') !== false) {
            $result['visitor_id'] = $params['visitor_id'];
        }

        return $result;
    }

    /**
     * Checks is visitor
     *
     * @param mixed $customer
     * @param bool $isFiltered
     * @return bool
     */
    protected function isVisitor($customer, $isFiltered)
    {
        return !$customer && $isFiltered;
    }

    /**
     * Return SELECT 0 statement
     *
     * @return \Magento\Framework\DB\Select
     */
    protected function getSqlForReturnZero()
    {
        return $this->getResource()->createSelect()->from(['' => new \Zend_Db_Expr('dual')], [new \Zend_Db_Expr(0)]);
    }
}
