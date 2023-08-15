<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Sales;

use Magento\Customer\Model\Customer;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

/**
 * Sales conditions combine
 */
abstract class Combine extends \Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine
{
    /**
     * @var string
     */
    protected $_inputType = 'numeric';

    /**
     * @var OrderResource
     */
    protected $orderResource;

    /**
     * Name of condition for displaying as html
     *
     * @var string
     */
    protected $frontConditionName = 'Combine';

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param OrderResource $orderResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        OrderResource $orderResource,
        array $data = []
    ) {
        $this->orderResource = $orderResource;
        parent::__construct($context, $conditionFactory, $resourceSegment, $data);
        $this->setType(get_class($this));
        $this->setValue(null);
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            '%1 %2 %3 %4 while %5 of these Conditions match:',
            $this->getAttributeElementHtml(),
            $this->frontConditionName,
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml(),
            $this->getAggregatorElement()->getHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return array_merge_recursive(
            parent::getNewChildSelectOptions(),
            [
                $this->_conditionFactory->create('Order\Status')->getNewChildSelectOptions(),
                // date ranges
                [
                    'value' => [
                        $this->_conditionFactory->create('Uptodate')->getNewChildSelectOptions(),
                        $this->_conditionFactory->create('Daterange')->getNewChildSelectOptions(),
                    ],
                    'label' => __('Date Ranges')
                ]
            ]
        );
    }

    /**
     * Init attribute select options
     *
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption(['total' => __('Total'), 'average' => __('Average')]);
        return $this;
    }

    /**
     * Get input type for attribute value.
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Check if validation should be strict
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    protected function _getRequiredValidation()
    {
        return true;
    }

    /**
     * Get field names map for subfilters
     *
     * @return array
     */
    protected function _getSubfilterMap()
    {
        return ['order' => 'sales_order.status', 'date' => 'sales_order.created_at'];
    }

    /**
     * @param int $customer
     * @param int $websiteId
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        $result = false;
        if (!$customer) {
            return $result;
        }
        $select = $this->getConditionsSql($customer, $websiteId);
        if (isset($select)) {
            $matchedParams = $this->matchParameters($select, $params);
            $result = $this->orderResource->getConnection()->fetchOne($select, $matchedParams);
        }
        return $result > 0;
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
            $result = $this->orderResource->getConnection()->fetchCol($select);
        }
        return $result;
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return string[]
     */
    public function getMatchedEvents()
    {
        return ['sales_order_save_commit_after'];
    }

    /**
     * Redeclare value options. We use empty because value is text input
     *
     * @return $this
     */
    public function loadValueOptions()
    {
        $this->setValueOption([]);
        return $this;
    }

    /**
     * Build conditions query
     *
     * @param Customer|Zend_Db_Expr $customer
     * @param int|Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return \Magento\Framework\DB\Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $select = $this->getResource()->createSelect();
        $operator = $this->getResource()->getSqlOperator($this->getOperator());
        $connection = $this->getResource()->getConnection();
        $value = $connection->quote((double) $this->getValue());

        $condition = $this->getConditionSql($operator, $value);

        if ($isFiltered) {
            $select->from(
                ['sales_order' => $this->getResource()->getTable('sales_order')],
                [$condition]
            );
        } else {
            $select->from(
                ['sales_order' => $this->getResource()->getTable('sales_order')],
                ['sales_order.customer_id']
            )->where('sales_order.customer_id IS NOT NULL');
            $select->group(['sales_order.customer_id']);
            $select->having($condition);
        }

        $this->_limitByStoreWebsite($select, $website, 'sales_order.store_id');

        if ($isFiltered) {
            $select->where($this->_createCustomerFilter($customer, 'sales_order.customer_id'));
        }
        return $select;
    }

    /**
     * Get query for condition
     *
     * @param string $operator
     * @param string $value
     * @return \Zend_Db_Expr
     */
    abstract protected function getConditionSql($operator, $value);
}
