<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Cart;

use Magento\Framework\DB\Select;

/**
 * Cart items subselection condition
 */
class Subselection extends \Magento\Reminder\Model\Condition\Combine\AbstractCombine
{
    /**
     * Cart Subcombine Factory
     *
     * @var \Magento\Reminder\Model\Rule\Condition\Cart\SubcombineFactory
     */
    protected $_subcombineFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    protected $quoteResource;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Reminder\Model\Rule\Condition\Cart\SubcombineFactory $subcombineFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Reminder\Model\Rule\Condition\Cart\SubcombineFactory $subcombineFactory,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        array $data = []
    ) {
        parent::__construct($context, $ruleResource, $data);
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Cart\Subselection::class);
        $this->_subcombineFactory = $subcombineFactory;
        $this->quoteResource = $quoteResource;
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return $this->_subcombineFactory->create()->getNewChildSelectOptions();
    }

    /**
     * Get element type for value select
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
        $this->setOperatorOption(['==' => __('found'), '!=' => __('not found')]);
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
            'If an item is %1 in the shopping cart and %2 of these conditions match:',
            $this->getOperatorElementHtml(),
            $this->getAggregatorElement()->getHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Build query for matching shopping cart items
     *
     * @param null|int|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return Select
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $select = $this->getResource()->createSelect();
        $quoteTable = $this->quoteResource->getTable('quote');
        $quoteItemTable = $this->quoteResource->getTable('quote_item');

        if ($isFiltered) {
            $select->from(['item' => $quoteItemTable], [new \Zend_Db_Expr(1)]);
            $select->joinInner(['quote' => $quoteTable], 'item.quote_id = quote.entity_id', []);
            $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
            $select->where($this->_createCustomerFilter($customer, 'quote.customer_id'));
            $select->limit(1);
        } else {
            $select->from(['item' => $quoteItemTable], '');
            $select->joinInner(['quote' => $quoteTable], 'item.quote_id = quote.entity_id', ['quote.customer_id']);
        }
        $select->where('quote.is_active = 1');

        return $select;
    }

    /**
     * {@inheritdoc}
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
                $customerIds = array_merge($satisfiedCustomerIds, $customerIds);
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
     * Check if validation should be strict
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    protected function _getRequiredValidation()
    {
        return $this->getOperator() == '==';
    }
}
