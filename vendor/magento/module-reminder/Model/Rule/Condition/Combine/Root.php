<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Combine;

use Magento\Customer\Model\Customer;
use Magento\Framework\DB\Select;

/**
 * Root rule condition (top level condition)
 */
class Root extends \Magento\Reminder\Model\Rule\Condition\Combine
{
    /**
     * Config
     *
     * @var \Magento\Customer\Model\Config\Share
     */
    protected $_config;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Customer\Model\Config\Share $config
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Customer\Model\Config\Share $config,
        array $data = []
    ) {
        parent::__construct($context, $ruleResource, $data);
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Combine\Root::class);
        $this->_config = $config;
    }

    /**
     * Prepare base select with limitation by customer
     *
     * @param null|array|int|Customer $customer
     * @param int|\Zend_Db_Expr $website
     * @return Select
     */
    protected function _prepareConditionsSql($customer, $website)
    {
        $select = $this->getResource()->createSelect();
        $rootTable = $this->getResource()->getTable('customer_entity');
        $couponTable = $this->getResource()->getTable('magento_reminder_rule_coupon');

        $select->from(['root' => $rootTable], ['entity_id']);

        $select->joinLeft(
            ['c' => $couponTable],
            'c.customer_id=root.entity_id AND c.rule_id=:rule_id',
            ['c.coupon_id']
        );

        if ($customer === null) {
            if ($this->_config->isWebsiteScope()) {
                $select->where('website_id=?', $website);
            }
        }
        return $select;
    }

    /**
     * Get SQL select.
     *
     * Rewritten for cover root conditions combination with additional condition by customer
     *
     * @param Customer|\Magento\Framework\DB\Select|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @return Select
     */
    public function getConditionsSql($customer, $website)
    {
        $select = $this->_prepareConditionsSql($customer, $website);
        $required = $this->_getRequiredValidation();
        $aggregator = $this->getAggregator() == 'all' ? ' AND ' : ' OR ';
        $operator = $required ? '=' : '<>';
        $conditions = [];

        foreach ($this->getConditions() as $condition) {
            $sql = $condition->getConditionsSql($customer, $website);
            if ($sql) {
                $conditions[] = '(' . $select->getConnection()->getIfNullSql("(" . $sql . ")", 0) . " {$operator} 1)";
            }
        }

        if (!empty($conditions)) {
            $select->where(implode($aggregator, $conditions));
        } else {
            $select->reset();
        }

        return $select;
    }

    /**
     * @param int $websiteId
     * @param null $ruleId
     * @param null $threshold
     * @return array
     */
    public function getSatisfiedIds($websiteId, $ruleId = null, $threshold = null)
    {
        $checkAll = $this->getAggregator() == 'all';
        $allMustBeTrue = $this->_getRequiredValidation();
        $result = [];
        $count = 0;
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
                $result = array_merge($customerIds, $result);
            }
            $count++;
        }

        //case when we don't have any condition
        if ($count == 0) {
            return [];
        } else {
            $rootSelect = $this->_prepareConditionsSql(null, $websiteId);
            $rootSelect->where('root.entity_id IN(?)', array_unique($result));
        }

        if ($threshold) {
            $rootSelect->where('c.emails_failed IS NULL OR c.emails_failed < ? ', $threshold);
        }

        return $this->getResource()->getConnection()->fetchAssoc($rootSelect, ['rule_id' => $ruleId]);
    }
}
