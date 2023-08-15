<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Cart;

use Magento\Framework\DB\Select;

/**
 * Cart items SKU sub-selection condition
 */
class Sku extends \Magento\Reminder\Model\Condition\AbstractCondition
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
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item
     */
    protected $quoteItemResource;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Store\Model\System\Store $store
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item $quoteItemResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Store\Model\System\Store $store,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        \Magento\Quote\Model\ResourceModel\Quote\Item $quoteItemResource,
        array $data = []
    ) {
        $this->_store = $store;
        $this->quoteResource = $quoteResource;
        $this->quoteItemResource = $quoteItemResource;
        parent::__construct($context, $ruleResource, $data);
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Cart\Sku::class);
        $this->setValue(null);
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return ['value' => $this->getType(), 'label' => __('SKU')];
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'Item SKU %1 %2 ',
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
        $this->setValueOption($this->_store->getStoreOptionHash());
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
        $quoteTable = $this->quoteResource->getTable('quote');
        $quoteItemTable = $this->quoteItemResource->getTable('quote_item');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());
        $select = $this->quoteItemResource->getConnection()->select();

        if ($isFiltered) {
            $select->from(['item' => $quoteItemTable], [new \Zend_Db_Expr(1)]);
            $select->joinInner(['quote' => $quoteTable], 'item.quote_id = quote.entity_id', []);
            $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
            $select->where($this->_createCustomerFilter($customer, 'quote.customer_id'));
            $select->limit(1);
        } else {
            $select->from(['item' => $quoteItemTable], ['quote.customer_id']);
            $select->joinInner(['quote' => $quoteTable], 'item.quote_id = quote.entity_id', []);
            $select->where('quote.customer_id IS NOT NULL');
        }
        $select->where('quote.is_active = 1');
        $select->where("item.sku {$operator} ?", $this->getValue());
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
        return $result;
    }
}
