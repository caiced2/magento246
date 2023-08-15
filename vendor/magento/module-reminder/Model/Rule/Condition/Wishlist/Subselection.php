<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Wishlist;

use Magento\Framework\DB\Select;

/**
 * Wishlist subselection condition
 */
class Subselection extends \Magento\Reminder\Model\Condition\Combine\AbstractCombine
{
    /**
     * Subcombine Factory
     *
     * @var \Magento\Reminder\Model\Rule\Condition\Wishlist\SubcombineFactory
     */
    protected $_subcombineFactory;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Reminder\Model\Rule\Condition\Wishlist\SubcombineFactory $subcombineFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\SubcombineFactory $subcombineFactory,
        array $data = []
    ) {
        parent::__construct($context, $ruleResource, $data);
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Wishlist\Subselection::class);
        $this->_subcombineFactory = $subcombineFactory;
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
            'If an item is %1 in the wish list and %2 of these conditions match:',
            $this->getOperatorElementHtml(),
            $this->getAggregatorElement()->getHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Build query for matching wishlist items
     *
     * @param null|int|\Zend_Db_Expr $customer
     * @param int|Zend_Db_Expr $website
     * @return Select
     */
    protected function _prepareConditionsSql($customer, $website)
    {
        $wishlistTable = $this->getResource()->getTable('wishlist');
        $wishlistItemTable = $this->getResource()->getTable('wishlist_item');

        $select = $this->getResource()->createSelect();
        $select->from(['item' => $wishlistItemTable], [new \Zend_Db_Expr(1)]);

        $select->joinInner(['list' => $wishlistTable], 'item.wishlist_id = list.wishlist_id', []);

        $this->_limitByStoreWebsite($select, $website, 'item.store_id');
        $select->where($this->_createCustomerFilter($customer, 'list.customer_id'));
        $select->limit(1);

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
     * Get field names map for sub-filter conditions
     *
     * @return array
     */
    protected function _getSubfilterMap()
    {
        return ['product' => 'item.product_id'];
    }
}
