<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Customer;

use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Condition\AbstractCondition;

/**
 * Customer store credit condition
 */
class Storecredit extends AbstractCondition
{
    /**
     * @var string
     */
    protected $_inputType = 'numeric';

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
        parent::__construct($context, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Customer\Storecredit::class);
        $this->setValue(null);
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return string[]
     */
    public function getMatchedEvents()
    {
        return ['customer_balance_save_commit_after'];
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return [['value' => $this->getType(), 'label' => __('Store Credit')]];
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        $operator = $this->getOperatorElementHtml();
        $element = $this->getValueElementHtml();
        return $this->getTypeElementHtml() . __(
            'Customer Store Credit Amount %1 %2:',
            $operator,
            $element
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Get condition query for customer balance on specific website
     *
     * @param Customer|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return \Magento\Framework\DB\Select
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        if ($this->isVisitor($customer, $isFiltered)) {
            return $this->getSqlForReturnZero();
        }
        $table = $this->getResource()->getTable('magento_customerbalance');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();
        if ($isFiltered) {
            $select->from($table, [new \Zend_Db_Expr(1)]);
            $select->where($this->_createCustomerFilter($customer, 'customer_id'));
            $select->limit(1);
        } else {
            $select->from($table, ['customer_id']);
        }
        $select->where('website_id=?', $website);
        $select->where("amount {$operator} ?", $this->getValue());

        return $select;
    }
}
