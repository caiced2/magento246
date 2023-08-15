<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Customer;

use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine;

/**
 * Customer address attributes conditions combine
 */
class Address extends AbstractCombine
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Eav\Model\Config $eavConfig,
        array $data = []
    ) {
        $this->_eavConfig = $eavConfig;
        parent::__construct($context, $conditionFactory, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Customer\Address::class);
    }

    /**
     * Get list of available sub-conditions
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $result = array_merge_recursive(
            parent::getNewChildSelectOptions(),
            [
                ['value' => $this->getType(), 'label' => __('Conditions Combination')],
                $this->_conditionFactory->create('Customer\Address\DefaultAddress')->getNewChildSelectOptions(),
                $this->_conditionFactory->create('Customer\Address\Attributes')->getNewChildSelectOptions()
            ]
        );
        return $result;
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'If Customer Addresses match %1 of these Conditions:',
            $this->getAggregatorElement()->getHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Condition presented without value select. Default value is "1"
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $resource = $this->getResource();
        $select = $resource->createSelect();
        $addressEntityType = $this->_eavConfig->getEntityType('customer_address');
        $addressTable = $resource->getTable($addressEntityType->getEntityTable());
        $customerTable = $this->getResource()->getTable('customer_entity');
        if ($isFiltered) {
            $select->from(['customer_address' => $addressTable], [new \Zend_Db_Expr(1)]);
            $select->where($this->_createCustomerFilter($customer, 'customer_address.parent_id'));
            $select->limit(1);
        } else {
            $select->from(['customer_address' => $addressTable], ['parent_id']);
        }
        $select->joinInner(
            ['customer' => $customerTable],
            'customer_address.parent_id = customer.entity_id',
            []
        );
        $this->_limitByStoreWebsite($select, $website, 'customer.store_id');

        return $select;
    }

    /**
     * Return status of executing query.
     *
     * @param int $customer
     * @param int $websiteId
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        $result = false;
        if (!$customer) {
            return false;
        }
        $select = $this->getConditionsSql($customer, $websiteId);
        if (isset($select)) {
            $matchedParams = $this->matchParameters($select, $params);
            $result = $this->getResource()->getConnection()->fetchOne($select, $matchedParams);
        }
        return $result > 0;
    }

    /**
     * Return result of executing query.
     *
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
