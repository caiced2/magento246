<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Cart;

use Magento\Framework\DB\Select;

/**
 * Cart items store view subselection condition
 */
class Storeview extends \Magento\Reminder\Model\Condition\AbstractCondition
{
    /**
     * Store
     *
     * @var \Magento\Store\Model\System\Store
     */
    protected $_store;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    protected $quoteResource;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Store\Model\System\Store $store
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Store\Model\System\Store $store,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        array $data = []
    ) {
        $this->_store = $store;
        parent::__construct($context, $ruleResource, $data);
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Cart\Storeview::class);
        $this->setValue(null);
        $this->quoteResource = $quoteResource;
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return ['value' => $this->getType(), 'label' => __('Store View')];
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'The item was added to shopping cart %1, store view %2.',
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Initialize value select options
     *
     * @return $this
     */
    public function loadValueOptions()
    {
        $this->setValueOption($this->_store->getStoreValuesForForm());
        return $this;
    }

    /**
     * Get select options
     *
     * @return array
     */
    public function getValueSelectOptions()
    {
        return $this->getValueOption();
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
        $this->setOperatorOption(['==' => __('from'), '!=' => __('not from')]);
        return $this;
    }

    /**
     * Get SQL select
     *
     * @param null|int|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return Select
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        $quoteTable = $this->getResource()->getTable('quote');
        $quoteItemTable = $this->getResource()->getTable('quote_item');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();

        if ($isFiltered) {
            $select->from(['item' => $quoteItemTable], [new \Zend_Db_Expr(1)]);
            $select->joinInner(['quote' => $quoteTable], 'item.quote_id = quote.entity_id', []);

            $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
            $select->where($this->_createCustomerFilter($customer, 'quote.customer_id'));
            $select->limit(1);
        } else {
            $select->from(['item' => $quoteItemTable], []);
            $select->joinInner(['quote' => $quoteTable], 'item.quote_id = quote.entity_id', ['customer_id']);
        }

        $select->where('quote.is_active = 1');
        $select->where("item.store_id {$operator} ?", $this->getValue());

        return $select;
    }

    /**
     * @param int $websiteId
     * @return array
     */
    public function getSatisfiedIds($websiteId)
    {
        $result = [];
        $storeIds = $this->getStoreByWebsite($websiteId);
        $select = $this->getConditionsSql(null, $websiteId, false);
        $select->where('quote.store_id IN(?)', $storeIds);

        if (isset($select)) {
            $result = $this->quoteResource->getConnection()->fetchCol($select);
        }
        return array_filter($result);
    }
}
