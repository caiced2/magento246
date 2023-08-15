<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Cart;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;

/**
 * Cart items attributes subselection condition
 */
class Attributes extends \Magento\Reminder\Model\Condition\AbstractCondition
{
    /**
     * @var string
     */
    protected $_inputType = 'numeric';

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    protected $quoteResource;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        array $data = []
    ) {
        parent::__construct($context, $ruleResource, $data);
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Cart\Attributes::class);
        $this->setValue(null);
        $this->quoteResource = $quoteResource;
    }

    /**
     * Get information for being presented in condition list
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return ['value' => $this->getType(), 'label' => __('Numeric Attribute')];
    }

    /**
     * Init available options list
     *
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption(
            [
                'weight' => __('weight'),
                'row_weight' => __('row weight'),
                'qty' => __('quantity'),
                'price' => __('base price'),
                'base_cost' => __('base cost'),
            ]
        );
        return $this;
    }

    /**
     * Condition string on conditions page
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'Item %1 %2 %3:',
            $this->getAttributeElementHtml(),
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Build condition limitations sql string for specific website
     *
     * @param null|int|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return Select
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        $quoteTable = $this->quoteResource->getTable('quote');
        $quoteItemTable = $this->quoteResource->getTable('quote_item');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->quoteResource->getConnection()->select();

        switch ($this->getAttribute()) {
            case 'weight':
                $field = 'item.weight';
                break;
            case 'row_weight':
                $field = 'item.row_weight';
                break;
            case 'qty':
                $field = 'item.qty';
                break;
            case 'price':
                $field = 'item.price';
                break;
            case 'base_cost':
                $field = 'item.base_cost';
                break;
            default:
                throw new LocalizedException(
                    __('The attribute specified is unknown. Verify the attribute and try again.')
                );
        }

        if ($isFiltered) {
            $select->from(['item' => $quoteItemTable], [new \Zend_Db_Expr(1)]);
            $select->joinInner(['quote' => $quoteTable], 'item.quote_id = quote.entity_id', []);
            $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
            $select->where($this->_createCustomerFilter($customer, 'quote.customer_id'));
        } else {
            $select->from(['item' => $quoteItemTable], '');
            $select->joinInner(['quote' => $quoteTable], 'item.quote_id = quote.entity_id', ['quote.customer_id']);
            $stores = $this->getStoreByWebsite($website);
            $select->where('quote.store_id in (?)', $stores);
            $select->where('quote.customer_id IS NOT NULL');
        }

        $select->where('quote.is_active = 1');
        $select->where("{$field} {$operator} ?", $this->getValue());
        $select->limit(1);
        return $select;
    }

    /**
     * Get customer ids matched condition
     *
     * @param int $websiteId
     * @return array
     */
    public function getSatisfiedIds($websiteId)
    {
        $result = [];
        $select = $this->getConditionsSql(null, $websiteId, false);
        if (isset($select)) {
            $result = $this->quoteResource->getConnection()->fetchCol($select);
        }
        return $result;
    }
}
