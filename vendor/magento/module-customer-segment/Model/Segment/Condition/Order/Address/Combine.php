<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Order\Address;

use Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine;

/**
 * Order address attribute conditions combine
 */
class Combine extends AbstractCombine
{
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
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Order\Address\Combine::class);
        $this->orderResource = $orderResource;
    }

    /**
     * @inheritdoc
     */
    public function getNewChildSelectOptions()
    {
        $result = array_merge_recursive(
            parent::getNewChildSelectOptions(),
            [
                ['value' => $this->getType(), 'label' => __('Conditions Combination')],
                $this->_conditionFactory->create(\Order\Address\Type::class)->getNewChildSelectOptions(),
                $this->_conditionFactory->create(\Order\Address\Attributes::class)->getNewChildSelectOptions()
            ]
        );
        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $select = $this->getResource()->createSelect();
        $table = $this->getOrderResource()->getTable('sales_order');
        $orderAddressTable = $this->getOrderResource()->getTable('sales_order_address');
        if ($isFiltered) {
            $select->from(['order_address_order' => $table], [new \Zend_Db_Expr(1)]);
            $select->where($this->_createCustomerFilter($customer, 'order_address_order.customer_id'));
        } else {
            $select->from(['order_address_order' => $table], ['order_address_order.customer_id']);
        }
        $select->join(
            ['order_address' => $orderAddressTable],
            'order_address.parent_id = order_address_order.entity_id',
            []
        )->limit(1);
        return $select;
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
        if (isset($select)) {
            $matchedParams = $this->matchParameters($select, $params);
            $result = $this->getOrderResource()->getConnection()->fetchOne($select, $matchedParams);
        }
        return $result > 0;
    }

    /**
     * @inheritdoc
     */
    public function getSatisfiedIds($websiteId)
    {
        $result = [];
        $select = $this->getConditionsSql(null, $websiteId, false);
        if (isset($select)) {
            $result = $this->getOrderResource()->getConnection()->fetchCol($select);
        }
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
