<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition;

use Magento\Framework\DB\Select;

/**
 * Customer wishlist conditions combine
 */
class Wishlist extends \Magento\Reminder\Model\Condition\Combine\AbstractCombine
{
    /**
     * Core Date
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_coreDate;

    /**
     * Core resource helper
     *
     * @var \Magento\Framework\DB\Helper
     */
    protected $_resourceHelper;

    /**
     * Wishlist Combine Factory
     *
     * @var \Magento\Reminder\Model\Rule\Condition\Wishlist\CombineFactory
     */
    protected $_combineFactory;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $coreDate
     * @param \Magento\Framework\DB\Helper $resourceHelper
     * @param \Magento\Reminder\Model\Rule\Condition\Wishlist\CombineFactory $combineFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\CombineFactory $combineFactory,
        array $data = []
    ) {
        parent::__construct($context, $ruleResource, $data);
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Wishlist::class);
        $this->setValue(null);
        $this->_coreDate = $coreDate;
        $this->_resourceHelper = $resourceHelper;
        $this->_combineFactory = $combineFactory;
    }

    /**
     * Get list of available subconditions
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return $this->_combineFactory->create()->getNewChildSelectOptions();
    }

    /**
     * Get input type for attribute value
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Override parent method
     *
     * @return $this
     */
    public function loadValueOptions()
    {
        $this->setValueOption([]);
        return $this;
    }

    /**
     * Prepare operator select options
     *
     * @return $this
     */
    public function loadOperatorOptions()
    {
        $this->setOperatorOption(
            ['==' => __('for'), '>' => __('for greater than'), '>=' => __('for or greater than')]
        );
        return $this;
    }

    /**
     * Return required validation
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    protected function _getRequiredValidation()
    {
        return true;
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'The wish list is not empty and abandoned %1 %2 days and %3 of these conditions match:',
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml(),
            $this->getAggregatorElement()->getHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Get condition SQL select
     *
     * @param null|int|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @return Select
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareConditionsSql($customer, $website)
    {
        $conditionValue = (int)$this->getValue();
        if ($conditionValue < 1) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The root wish list condition should have a days value of 1 or greater.')
            );
        }

        $wishlistTable = $this->getResource()->getTable('wishlist');
        $wishlistItemTable = $this->getResource()->getTable('wishlist_item');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();
        $select->from(['item' => $wishlistItemTable], [new \Zend_Db_Expr(1)]);

        $select->joinInner(['list' => $wishlistTable], 'item.wishlist_id = list.wishlist_id', []);

        $this->_limitByStoreWebsite($select, $website, 'item.store_id');

        $currentTime = $this->_coreDate->gmtDate();
        $daysDiffSql = $this->_resourceHelper->getDateDiff(
            'list.updated_at',
            $select->getConnection()->formatDate($currentTime)
        );
        $select->where($daysDiffSql . " {$operator} ?", $conditionValue);
        $select->where($this->_createCustomerFilter($customer, 'list.customer_id'));
        $select->limit(1);
        return $select;
    }

    /**
     * Get base SQL select
     *
     * @param null|int|\Zend_Db_Expr $customer
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
                $conditions[] = "(" . $select->getConnection()->getIfNullSql("(" . $sql . ")", 0) . " {$operator} 1)";
            }
        }

        if (!empty($conditions)) {
            $select->where(implode($aggregator, $conditions));
        }

        return $select;
    }
}
