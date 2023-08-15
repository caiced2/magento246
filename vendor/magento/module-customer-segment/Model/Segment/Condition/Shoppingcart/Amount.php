<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart;

use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Condition\AbstractCondition;

/**
 * Shopping cart totals amount condition
 */
class Amount extends AbstractCondition
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
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart\Amount::class);
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
        return ['sales_quote_save_commit_after', 'checkout_cart_save_after', 'sales_quote_collect_totals_after'];
    }

    /**
     * Get information for being presented in condition list
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return [
            'value' => $this->getType(),
            'label' => __('Shopping Cart Total'),
            'available_in_guest_mode' => true
        ];
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
                'subtotal' => __('Subtotal'),
                'grand_total' => __('Grand Total'),
                'tax' => __('Tax'),
                'shipping' => __('Shipping'),
                'store_credit' => __('Store Credit'),
                'gift_card' => __('Gift Card'),
            ]
        );
        return $this;
    }

    /**
     * Set rule instance
     *
     * Modify attribute_option array if needed
     *
     * @param \Magento\Rule\Model\AbstractModel $rule
     * @return $this
     */
    public function setRule($rule)
    {
        $this->setData('rule', $rule);
        if ($rule instanceof \Magento\CustomerSegment\Model\Segment && $rule->getApplyTo() !== null) {
            $attributeOption = $this->loadAttributeOptions()->getAttributeOption();
            $applyTo = $rule->getApplyTo();
            if (\Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS == $applyTo) {
                unset($attributeOption['store_credit']);
            } elseif (\Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS_AND_REGISTERED == $applyTo) {
                foreach (array_keys($attributeOption) as $key) {
                    if ('store_credit' != $key) {
                        $attributeOption[$key] .= '*';
                    }
                }
            }
            $this->setAttributeOption($attributeOption);
        }
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
            'Shopping Cart %1 Amount %2 %3:',
            $this->getAttributeElementHtml(),
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Build condition limitations sql string for specific website
     *
     * @param Customer|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\DB\Select
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        $table = $this->getResource()->getTable('quote');
        $addressTable = $this->getResource()->getTable('quote_address');
        $operator = $this->getResource()->getSqlOperator($this->getOperator());

        $select = $this->getResource()->createSelect();
        $select->from(['quote' => $table], [new \Zend_Db_Expr(1)])->where('quote.is_active=1');
        $select->limit(1);
        $this->_limitByStoreWebsite($select, $website, 'quote.store_id');

        $joinAddress = false;
        switch ($this->getAttribute()) {
            case 'subtotal':
                $field = 'quote.base_subtotal';
                break;
            case 'grand_total':
                $field = 'quote.base_grand_total';
                break;
            case 'tax':
                $field = 'base_tax_amount';
                $joinAddress = true;
                break;
            case 'shipping':
                $field = 'base_shipping_amount';
                $joinAddress = true;
                break;
            case 'store_credit':
                $field = 'quote.base_customer_bal_amount_used';
                break;
            case 'gift_card':
                $field = 'quote.base_gift_cards_amount_used';
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The quote total specified is unknown. Verify the total and try again.')
                );
        }

        if ($joinAddress) {
            $subSelect = $this->getResource()->createSelect();
            $subSelect->from(
                ['address' => $addressTable],
                ['quote_id' => 'quote_id', $field => new \Zend_Db_Expr("SUM({$field})")]
            );

            $subSelect->group('quote_id');
            $select->joinInner(['address' => $subSelect], 'address.quote_id = quote.entity_id', []);
            $field = "address.{$field}";
        }

        $select->where("{$field} {$operator} ?", $this->getValue());
        if ($customer) {
            // Leave ability to check this condition not only by customer_id but also by quote_id
            $select->where('quote.customer_id = :customer_id OR quote.entity_id = :quote_id');
        } else {
            $select->where($this->_createCustomerFilter($customer, 'quote.customer_id'));
        }
        return $select;
    }

    /**
     * Build and execute sql query.
     *
     * @param int $customer
     * @param int $websiteId
     * @param array $params
     * @param bool $isFiltered
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function executeSql($customer, $websiteId, $params, $isFiltered = true)
    {
        $table = $this->getResource()->getTable('quote');
        $addressTable = $this->getResource()->getTable('quote_address');
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

        $joinAddress = false;
        switch ($this->getAttribute()) {
            case 'subtotal':
                $field = 'quote.base_subtotal';
                break;
            case 'grand_total':
                $field = 'quote.base_grand_total';
                break;
            case 'tax':
                $field = 'base_tax_amount';
                $joinAddress = true;
                break;
            case 'shipping':
                $field = 'base_shipping_amount';
                $joinAddress = true;
                break;
            case 'store_credit':
                $field = 'quote.base_customer_bal_amount_used';
                break;
            case 'gift_card':
                $field = 'quote.base_gift_cards_amount_used';
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The quote total specified is unknown. Verify the total and try again.')
                );
        }

        if ($joinAddress) {
            $subSelect = $this->getResource()->createSelect();
            $subSelect->from(
                ['address' => $addressTable],
                ['quote_id' => 'quote_id', $field => new \Zend_Db_Expr("SUM({$field})")]
            );

            $subSelect->group('quote_id');
            $select->joinInner(['address' => $subSelect], 'address.quote_id = quote.entity_id', []);
            $field = "address.{$field}";
        }

        $select->where("{$field} {$operator} ?", $this->getValue());
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
     * Return status of executing query.
     *
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
     * Return result of executing query.
     *
     * @param int $websiteId
     * @return array
     */
    public function getSatisfiedIds($websiteId)
    {
        return $this->executeSql(null, $websiteId, [], false);
    }
}
