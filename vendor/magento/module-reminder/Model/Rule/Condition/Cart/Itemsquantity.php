<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Cart;

use Magento\Framework\DB\Select;

/**
 * Cart items quantity condition
 */
class Itemsquantity extends \Magento\Reminder\Model\Condition\AbstractCondition
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
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Cart\Itemsquantity::class);
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
        return ['value' => $this->getType(), 'label' => __('Cart Line Items')];
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'Number of shopping cart line items %1 %2:',
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Get SQL select for matching shopping cart items count
     *
     * @param null|int|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return Select
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        $table = $this->quoteResource->getTable('quote');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());
        $select = $this->quoteResource->getConnection()->select();

        if ($isFiltered) {
            $select->from(['quote' => $table], [new \Zend_Db_Expr(1)]);
            $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
            $select->where($this->_createCustomerFilter($customer, 'quote.customer_id'));
            $select->limit(1);
        } else {
            $select->from(['quote' => $table], ['quote.customer_id']);
            $select->where('quote.customer_id IS NOT NULL');
        }

        $select->where('quote.is_active = 1');
        $select->where("quote.items_count {$operator} ?", $this->getValue());
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
