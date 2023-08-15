<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart;

use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Condition\AbstractCondition;

class Itemsquantity extends AbstractCondition
{
    /**
     * @var string
     */
    protected $_inputType = 'numeric';

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    protected $resourceQuote;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Quote\Model\ResourceModel\Quote $resourceQuote
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Quote\Model\ResourceModel\Quote $resourceQuote,
        array $data = []
    ) {
        parent::__construct($context, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Itemsquantity::class);
        $this->setValue(null);
        $this->resourceQuote = $resourceQuote;
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return string[]
     */
    public function getMatchedEvents()
    {
        return ['sales_quote_save_commit_after', 'sales_quote_collect_totals_after'];
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return [
            'value' => $this->getType(),
            'label' => __('Number of Cart Line Items'),
            'available_in_guest_mode' => true
        ];
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'Number of Shopping Cart Line Items %1 %2:',
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Get SQL select for matching shopping cart items count
     *
     * @param int $customerId
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return \Magento\Framework\DB\Select
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConditionsSql($customerId, $website, $isFiltered = true)
    {
        $table = $this->getResource()->getTable('quote');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();
        $select->from(['quote' => $table], [new \Zend_Db_Expr(1)])->where('quote.is_active=1');
        $this->_limitByStoreWebsite($select, $website, 'quote.store_id');
        $select->limit(1);
        $select->where("quote.items_count {$operator} ?", $this->getValue());
        if ($customerId) {
            // Leave ability to check this condition not only by customer_id but also by quote_id
            $select->where('quote.customer_id = :customer_id OR quote.entity_id = :quote_id');
        } else {
            $select->where($this->_createCustomerFilter($customerId, 'quote.customer_id'));
        }

        return $select;
    }

    /**
     * @param int|Customer $customer
     * @param int $websiteId
     * @param array $params
     * @param bool $isFiltered
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function executeSql($customer, $websiteId, $params, $isFiltered = true)
    {
        $table = $this->getResource()->getTable('quote');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();

        if ($isFiltered) {
            $select->from(['quote' => $table], [new \Zend_Db_Expr(1)])->where('quote.is_active=1');
            $select->limit(1);
        } else {
            $select->from(['quote' => $table], ['customer_id'])->where('quote.is_active=1');
        }
        $select->where(
            'quote.store_id IN(?)',
            $this->getStoreByWebsite($websiteId)
        );

        $select->where("quote.items_count {$operator} ?", $this->getValue());
        if ($isFiltered) {
            // Leave ability to check this condition not only by customer_id but also by quote_id
            $contextFilter = ['quote.entity_id = :quote_id'];
            if (!empty($params['customer_id'])) {
                $contextFilter[] = 'quote.customer_id = :customer_id';
            }
            $select->where(implode(' OR ', $contextFilter));
        } else {
            $select->where('customer_id IS NOT NULL');
        }
        $matchedParams = $this->matchParameters($select, $params);
        $result = $this->resourceQuote->getConnection()->fetchCol($select, $matchedParams);
        return $result;
    }

    /**
     * @param int $customer
     * @param int $websiteId
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        $result = $this->executeSql($customer, $websiteId, $params, true);
        return !empty($result);
    }

    /**
     * @param int $websiteId
     * @param null $requireValid
     * @return array
     */
    public function getSatisfiedIds($websiteId)
    {
        return $this->executeSql(null, $websiteId, [], false);
    }
}
