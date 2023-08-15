<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Order;

use Magento\CustomerSegment\Model\Segment\Condition\Order\Address\Combine;

/**
 * Order address condition
 */
class Address extends \Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine
{
    /**
     * @var string
     */
    protected $_inputType = 'select';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    protected $orderResource;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        array $data = []
    ) {
        parent::__construct($context, $conditionFactory, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Order\Address::class);
        $this->orderResource = $orderResource;
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
     * Get List of available selections inside this combine
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return $this->_conditionFactory
            ->create(Combine::class)
            ->getNewChildSelectOptions();
    }

    /**
     * Get html of order address combine
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'If Order Addresses match %1 of these Conditions:',
            $this->getAggregatorElement()->getHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Order address combine doesn't have declared value. We use "1" for it
     *
     * @return int
     */
    public function getValue()
    {
        return 1;
    }

    /**
     * Prepare base condition select which related with current condition combine
     *
     * @param Customer|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return \Magento\Framework\DB\Select
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $select = $this->getResource()->createSelect();

        $mainAddressTable = $this->getOrderResource()->getTable('sales_order_address');
        $extraAddressTable = $this->getOrderResource()
            ->getTable('magento_customercustomattributes_sales_flat_order_address');
        $orderTable = $this->getOrderResource()->getTable('sales_order');

        if ($isFiltered) {
            $select->from(
                ['order_address' => $mainAddressTable],
                [new \Zend_Db_Expr(1)]
            );
        } else {
            $select->from(
                ['order_address' => $mainAddressTable],
                ['order_address_order.customer_id']
            );
        }
        $select->join(
            ['order_address_order' => $orderTable],
            'order_address.parent_id = order_address_order.entity_id',
            []
        )->joinLeft(
            ['extra_order_address' => $extraAddressTable],
            'order_address.entity_id = extra_order_address.entity_id',
            []
        );
        if ($isFiltered) {
            $select->where(
                $this->_createCustomerFilter($customer, 'order_address_order.customer_id')
            );
            $select->limit(1);
        } else {
            $select->where('order_address_order.customer_id IS NOT NULL');
        }
        $this->_limitByStoreWebsite($select, $website, 'order_address_order.store_id');
        return $select;
    }

    /**
     * Order address is joined to base query. We are applying address type condition as subfilter for main query
     *
     * @return array
     */
    protected function _getSubfilterMap()
    {
        return ['order_address_type' => 'order_address_type.value'];
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        $result = false;
        if (!$customer) {
            return $result;
        }
        $select = $this->getConditionsSql($customer, $websiteId);
        $matchedParams = $this->matchParameters($select, $params);
        $result = $this->getOrderResource()->getConnection()->fetchOne($select, $matchedParams);
        return $result > 0;
    }

    /**
     * @inheritdoc
     */
    public function getSatisfiedIds($websiteId)
    {
        $select = $this->getConditionsSql(null, $websiteId, false);
        $result = $this->getOrderResource()->getConnection()->fetchCol($select);
        return $result;
    }

    /**
     * Get order resource for get separate connection
     *
     * @return \Magento\Sales\Model\ResourceModel\Order
     */
    protected function getOrderResource()
    {
        return $this->orderResource;
    }
}
