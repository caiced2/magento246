<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Product\Combine;

use Magento\Customer\Model\Customer;
use Magento\Framework\DB\Select;
use Zend_Db_Expr;

/**
 * Last viewed/orderd items conditions combine
 */
class History extends \Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine
{
    /**
     * Sales resource model.
     *
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    protected $resourceOrder;

    /**
     * Flag of using condition combine (for conditions of Product_Attribute)
     *
     * @var bool
     */
    protected $_combineProductCondition = true;

    public const VIEWED = 'viewed_history';

    public const ORDERED = 'ordered_history';

    /**
     * @var string
     */
    protected $_inputType = 'select';

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Sales\Model\ResourceModel\Order $resourceOrder
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Sales\Model\ResourceModel\Order $resourceOrder,
        array $data = []
    ) {
        parent::__construct($context, $conditionFactory, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History::class);
        $this->setValue(self::VIEWED);

        $this->resourceOrder = $resourceOrder;
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return string[]
     */
    public function getMatchedEvents()
    {
        switch ($this->getValue()) {
            case self::ORDERED:
                $events = ['sales_order_save_commit_after'];
                break;
            default:
                $events = ['catalog_controller_product_view'];
        }
        return $events;
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return $this->_conditionFactory->create(
            'Product\Combine'
        )->setDateConditions(
            true
        )->getNewChildSelectOptions();
    }

    /**
     * Initialize value select options
     *
     * @return $this
     */
    public function loadValueOptions()
    {
        $this->setValueOption([self::VIEWED => __('viewed'), self::ORDERED => __('ordered')]);
        return $this;
    }

    /**
     * Set rule instance
     *
     * Modify value_option array if needed
     *
     * @param \Magento\Rule\Model\AbstractModel $rule
     * @return $this
     */
    public function setRule($rule)
    {
        $this->setData('rule', $rule);
        if ($rule instanceof \Magento\CustomerSegment\Model\Segment && $rule->getApplyTo() !== null) {
            $option = $this->loadValueOptions()->getValueOption();
            $applyTo = $rule->getApplyTo();
            if (\Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS == $applyTo) {
                unset($option[self::ORDERED]);
            } elseif (\Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS_AND_REGISTERED == $applyTo) {
                $option[self::VIEWED] .= '*';
            }
            $this->setValueOption($option);
        }
        return $this;
    }

    /**
     * Get input type for attribute value.
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * Prepare operator select options
     *
     * @return $this
     */
    public function loadOperatorOptions()
    {
        parent::loadOperatorOptions();
        $this->setOperatorOption(['==' => __('was'), '!=' => __('was not')]);
        return $this;
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'If Product %1 %2 and matches %3 of these Conditions:',
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml(),
            $this->getAggregatorElement()->getHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Process customer id value
     *
     * @param Customer|int|Zend_Db_Expr $customer
     * @return int|null|Zend_Db_Expr
     */
    private function preprocessCustomerId($customer)
    {
        if ($customer instanceof Zend_Db_Expr) {
            return $customer;
        }
        if ($customer instanceof Customer && $customer->getId()) {
            return $customer->getId();
        }
        if (is_numeric($customer)) {
            return (int) $customer;
        }
        return null;
    }

    /**
     * Build query for matching last viewed/orderd items
     *
     * @param Customer|int|Zend_Db_Expr $customer
     * @param int|Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return Select
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $select = $this->getResource()->createSelect();

        if ($this->getValue() == self::ORDERED) {
            return $this->prepareOrderedSelect($select, $customer, $website, $isFiltered);
        } else {
            return $this->prepareViewedSelect($select, $customer, $website, $isFiltered);
        }
    }

    /**
     * Prepare select for viewed products
     *
     * @param Select $select
     * @param Customer|int|Zend_Db_Expr $customer
     * @param int|Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return Select
     */
    private function prepareViewedSelect(Select $select, $customer, $website, $isFiltered = true)
    {
        if ($isFiltered) {
            $select->from(
                ['item' => $this->getResource()->getTable('report_viewed_product_index')],
                [new \Zend_Db_Expr(1)]
            );
            $select->limit(1);
        } else {
            $select->from(
                ['item' => $this->getResource()->getTable('report_viewed_product_index')],
                ['customer_id']
            );
        }
        if ($customer) {
            // Leave ability to check this condition not only by customer_id but also by quote_id
            $select->where('item.customer_id = :customer_id OR item.visitor_id = :visitor_id');
        } elseif ($isFiltered && $customer) {
            $select->where($this->_createCustomerFilter($customer, 'item.customer_id'));
        } elseif ($isFiltered && !$customer) {
            $select->where('item.visitor_id = :visitor_id');
        }
        $this->_limitByStoreWebsite($select, $website, 'item.store_id');
        return $select;
    }

    /**
     * Prepare select for ordered products
     *
     * @param Select $select
     * @param Customer|int|Zend_Db_Expr $customer
     * @param int|Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return Select
     */
    private function prepareOrderedSelect(Select $select, $customer, $website, $isFiltered = true)
    {
        if ($isFiltered) {
            $select->from(
                ['item' => $this->getResource()->getTable('sales_order_item')],
                [new \Zend_Db_Expr(1)]
            );
        } else {
            $select->from(
                ['item' => $this->getResource()->getTable('sales_order_item')],
                ['sales_order.customer_id']
            )->where('sales_order.customer_id IS NOT NULL');
        }
        $select->joinInner(
            ['sales_order' => $this->getResource()->getTable('sales_order')],
            'item.order_id = sales_order.entity_id',
            []
        );

        $customerId = $this->preprocessCustomerId($customer);
        if ($customerId) {
            $select->where('customer_id = ?', $customerId);
        }
        $this->_limitByStoreWebsite($select, $website, 'sales_order.store_id');
        return $select;
    }

    /**
     * Check if validation should be strict
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    protected function _getRequiredValidation()
    {
        return $this->getOperator() == '==';
    }

    /**
     * Get field names map for subfilter conditions
     *
     * @return array
     */
    protected function _getSubfilterMap()
    {
        switch ($this->getValue()) {
            case self::ORDERED:
                $dateField = 'item.created_at';
                break;

            default:
                $dateField = 'item.added_at';
                break;
        }

        return ['product' => 'item.product_id', 'date' => $dateField];
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        if ($customer === null) {
            return false;
        }
        $this->setData(
            'product_ids',
            $this->getProductIdsByCustomer($customer, $websiteId, $params)
        );

        if (!$this->getData('product_ids')) {
            return '!=' === $this->getOperator();
        }

        $select = $this->getConditionsSql($customer, $websiteId);

        $matchedParams = $this->matchParameters($select, $params);

        $result = $this->getResource(true)->getConnection()->fetchOne($select, $matchedParams) > 0;
        if ('!=' === $this->getOperator()) {
            $result = !$result;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getSatisfiedIds($websiteId)
    {
        $this->setData(
            'product_ids',
            $this->getNotFilteredProductIds($websiteId)
        );

        if (!$this->getData('product_ids')) {
            return [];
        }

        $select = $this->getConditionsSql(null, $websiteId, false);
        $customerIds = $this->getResource(true)->getConnection()->fetchCol($select);
        if ($this->_getRequiredValidation()) {
            return $customerIds;
        }
        //not matched
        $select = $this->getResource()->createSelect();
        $table = ['root' => $this->getResource()->getTable('customer_entity')];
        $select->from($table, ['entity_id', 'website_id']);
        if (!empty($customerIds)) {
            $select->where('entity_id NOT IN (?)', $customerIds);
        }
        $this->_limitByStoreWebsite($select, $websiteId, 'root.website_id');
        $customerIds = $this->getResource()->getConnection()->fetchCol($select);
        return $customerIds;
    }

    /**
     * Get not filtered product ids.
     *
     * @param int $websiteId
     * @return array
     */
    protected function getNotFilteredProductIds($websiteId)
    {
        $select = $this->_prepareConditionsSql(null, $websiteId, false);
        return $this->getProductIds($select, []);
    }

    /**
     * Get product ids by customer.
     *
     * @param int|\Magento\Framework\DataObject $customer
     * @param int $websiteId
     * @param array $params
     * @return array
     */
    protected function getProductIdsByCustomer($customer, $websiteId, $params)
    {
        $select = $this->_prepareConditionsSql($customer, $websiteId);
        return $this->getProductIds($select, $params);
    }

    /**
     * Get product ids.
     *
     * @param Select $select
     * @param array $params
     * @return array
     */
    protected function getProductIds($select, $params)
    {
        $select->reset(Select::COLUMNS);
        $select->columns('item.product_id');
        $params = $this->matchParameters($select, $params);
        $select->group('item.product_id');
        return $this->getResource(true)->getConnection()->fetchCol($select, $params);
    }

    /**
     * @inheritdoc
     */
    protected function processCombineSubFilters($website, $required, array $conditions)
    {
        $subfilterMap = $this->_getSubfilterMap();
        if ($subfilterMap) {
            foreach ($this->getConditions() as $condition) {
                $subfilterType = $condition->getSubfilterType();
                if (isset($subfilterMap[$subfilterType])) {
                    $condition->setCombineProductCondition($this->_combineProductCondition);
                    if ($condition instanceof \Magento\Framework\DataObject) {
                        $condition->setData('product_ids', $this->getData('product_ids'));
                        $condition->setUseSelect(!$this->hasData('product_ids'));
                    }
                    $subfilter = $condition->getSubfilterSql($subfilterMap[$subfilterType], $required, $website);
                    if ($subfilter) {
                        $conditions[] = $subfilter;
                    }
                }
            }
        }
        return $conditions;
    }

    /**
     * Gets corresponding resource model.
     *
     * If parameter $smartMode is FALSE, Segment resource model will be returned.
     * Otherwise resource model will be returned in according to current condition value.
     *
     * @param bool $smartMode
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    public function getResource($smartMode = false)
    {
        if ($smartMode && $this->getValue() == self::ORDERED) {
            return $this->resourceOrder;
        }

        return $this->_resourceSegment;
    }
}
