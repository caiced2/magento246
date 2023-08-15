<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;

/**
 * Customer cart conditions combine
 */
class Cart extends \Magento\Reminder\Model\Condition\Combine\AbstractCombine
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateModel;

    /**
     * Core resource helper
     *
     * @var \Magento\Framework\DB\Helper
     */
    protected $_resourceHelper;

    /**
     * Cart Combine Factory
     *
     * @var \Magento\Reminder\Model\Rule\Condition\Cart\CombineFactory
     */
    protected $_combineFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    protected $quoteResource;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateModel
     * @param \Magento\Framework\DB\Helper $resourceHelper
     * @param Cart\CombineFactory $combineFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateModel,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Reminder\Model\Rule\Condition\Cart\CombineFactory $combineFactory,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        array $data = []
    ) {
        parent::__construct($context, $ruleResource, $data);
        $this->_dateModel = $dateModel;
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Cart::class);
        $this->setValue(null);
        $this->_resourceHelper = $resourceHelper;
        $this->_combineFactory = $combineFactory;
        $this->quoteResource = $quoteResource;
    }

    /**
     * Get list of available sub conditions
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
            'Shopping cart is not empty and abandoned %1 %2 days and %3 of these conditions match:',
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
     * @param bool $isFiltered
     * @return Select
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $conditionValue = (int)$this->getValue();
        if ($conditionValue < 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The root shopping cart condition should have a days value of 0 or greater.')
            );
        }

        $table = $this->quoteResource->getTable('quote');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());
        $currentTime = $this->_dateModel->gmtDate('Y-m-d H:i:s');
        $select = $this->getResource()->createSelect();

        $daysDiffSql = $this->_resourceHelper->getDateDiff(
            $select->getConnection()->getCheckSql('quote.updated_at = 0', 'quote.created_at', 'quote.updated_at'),
            $select->getConnection()->formatDate($currentTime)
        );
        if ($operator == '>=' && $conditionValue == 0) {
            $currentTime = $this->_dateModel->gmtDate();
            $daysDiffSql = $this->_resourceHelper->getDateDiff(
                $select->getConnection()->getCheckSql('quote.updated_at = 0', 'quote.created_at', 'quote.updated_at'),
                $select->getConnection()->formatDate($currentTime)
            );
        }

        if ($isFiltered) {
            $select->from(['quote' => $table], [new \Zend_Db_Expr(1)]);
            $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
            $select->where($this->_createCustomerFilter($customer, 'quote.customer_id'));
            $select->limit(1);
        } else {
            $select->from(['quote' => $table], ['customer_id']);
        }

        $select->where($daysDiffSql . " {$operator} ?", $conditionValue);
        $select->where('quote.is_active = 1');
        $select->where('quote.items_count > 0');
        return $select;
    }

    /**
     * @inheritdoc
     *
     * @param int $websiteId
     * @return array
     * @throws LocalizedException
     */
    public function getSatisfiedIds($websiteId)
    {
        $checkAll = $this->getAggregator() == 'all';
        $allMustBeTrue = $this->_getRequiredValidation();
        $customerIds = [];

        /** @var \Magento\Reminder\Model\Condition\Combine\AbstractCombine $condition */
        foreach ($this->getConditions() as $key => $condition) {
            if ($allMustBeTrue) {
                // customer IDs that match child condition
                $satisfiedCustomerIds = $condition->getSatisfiedIds($websiteId);
            } else {
                // customer IDs that do not match child condition
                $satisfiedCustomerIds = $this->getCustomerIds($websiteId, $condition->getSatisfiedIds($websiteId));
            }
            if ($checkAll) {
                if ($key > 0) {
                    $customerIds = array_intersect($condition->getSatisfiedIds($websiteId), $customerIds);
                } else {
                    $customerIds = $satisfiedCustomerIds;
                }
                if (empty($customerIds)) {
                    return [];
                }
            } else {
                array_push($customerIds, ...$satisfiedCustomerIds);
            }
        }

        $select = $this->_prepareConditionsSql(null, $websiteId, false);
        $storeIds = $this->getStoreByWebsite($websiteId);
        $select->where('quote.store_id IN(?)', $storeIds);

        if ($customerIds) {
            $select->where('quote.customer_id IN(?)', array_unique($customerIds));
        }
        return $this->quoteResource->getConnection()->fetchCol($select);
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
