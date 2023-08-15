<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Condition;

/**
 * Abstract class for rule condition
 */
class AbstractCondition extends \Magento\Rule\Model\Condition\AbstractCondition
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
     * @return array|null
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
     * Get condition combine resource model
     *
     * @return \Magento\Reminder\Model\ResourceModel\Rule
     */
    public function getResource()
    {
        return $this->_ruleResource;
    }

    /**
     * Generate customer condition string
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
     * Get assigned to website store ids
     *
     * @TODO move this method to rule module
     * @param int $websiteId
     * @return array
     */
    protected function getStoreByWebsite($websiteId)
    {
        $storeTable = $this->getResource()->getTable('store');
        $connection = $this->getResource()->getConnection();
        $storeSelect = $connection->select()->from($storeTable, ['store_id'])->where(
            'website_id=?',
            $websiteId
        );
        $data = $connection->fetchCol($storeSelect);
        return $data;
    }

    /**
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
}
