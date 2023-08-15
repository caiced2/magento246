<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Combine;

use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine;
use Zend_Db_Expr;
use Magento\Framework\DB\Select;

/**
 * Root segment condition (top level condition)
 */
class Root extends \Magento\CustomerSegment\Model\Segment\Condition\Combine
{
    /**
     * @var \Magento\Customer\Model\Config\Share
     */
    protected $_configShare;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Customer\Model\Config\Share $configShare
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Customer\Model\Config\Share $configShare,
        array $data = []
    ) {
        parent::__construct($context, $conditionFactory, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Combine\Root::class);
        $this->_configShare = $configShare;
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return string[]
     */
    public function getMatchedEvents()
    {
        return ['customer_login', 'customer_save_commit_after'];
    }

    /**
     * Prepare filter condition by customer
     *
     * @param int|array|Customer|Select $customer
     * @param string $fieldName
     * @return string
     */
    protected function _createCustomerFilter($customer, $fieldName)
    {
        if ($customer instanceof Customer) {
            $customer = $customer->getId();
        } elseif ($customer instanceof \Magento\Framework\DB\Select) {
            $customer = new Zend_Db_Expr($customer);
        }

        return $this->getResource()->quoteInto("{$fieldName} IN (?)", $customer);
    }

    /**
     * Prepare base select with limitation by customer
     *
     * @param   null|array|int|Customer $customer
     * @param   int|Zend_Db_Expr $website
     * @param   bool $isFiltered
     * @return  \Magento\Framework\DB\Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $select = $this->getResource()->createSelect();
        $table = ['root' => $this->getResource()->getTable('customer_entity')];

        if ($customer) {
            // For existing customer
            $select->from($table, new Zend_Db_Expr(1));
        } else {
            $select->from($table, ['entity_id', 'website_id']);
            if ($customer === null && $this->_configShare->isWebsiteScope()) {
                $select->where('website_id=?', $website);
            }
        }

        return $select;
    }

    /**
     * @inheritdoc
     *
     * Customers may be required to match or NOT to match conditions.
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        $mustBeTrue = $this->_getRequiredValidation();
        $checkAll = $this->getAggregator() == 'all';
        /** @var AbstractCombine[] $conditions */
        $conditions = $this->getConditions();
        foreach ($conditions as $condition) {
            $result = $condition->isSatisfiedBy($customer, $websiteId, $params);
            //If customer is expected NOT to match conditions then reversing
            //the condition's decision.
            if (!$mustBeTrue) {
                $result = !$result;
            }
            if (!$result && $checkAll) {
                $checkAll = false;
                break;
            }
            if ($result && !$checkAll) {
                $checkAll = true;
                break;
            }
        }

        return $checkAll;
    }
}
