<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition;

use Zend_Db_Expr;

/**
 * Period "Last N Days" condition class
 */
class Uptodate extends \Magento\CustomerSegment\Model\Condition\AbstractCondition
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
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        array $data = []
    ) {
        parent::__construct($context, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Uptodate::class);
        $this->setValue(null);
        $this->quoteResource = $quoteResource;
    }

    /**
     * Customize default operator input by type mapper for some types
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['numeric'] = ['>=', '<=', '>', '<'];
        }
        return $this->_defaultOperatorInputByType;
    }

    /**
     * Customize default operator options getter
     *
     * Inverted logic for UpToDate condition. For example, condition:
     * Period "equals or less" than 10 Days Up To Date - means:
     * days from _10 day before today_ till today: days >= (today - 10), etc.
     *
     * @return array
     */
    public function getDefaultOperatorOptions()
    {
        if (null === $this->_defaultOperatorOptions) {
            $this->_defaultOperatorOptions = [
                '<=' => __('equals or greater than'),
                '>=' => __('equals or less than'),
                '<' => __('greater than'),
                '>' => __('less than'),
            ];
        }
        return $this->_defaultOperatorOptions;
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return ['value' => $this->getType(), 'label' => __('Up To Date')];
    }

    /**
     * Get element input value type
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        return $this->getTypeElementHtml() . __(
            'Period %1 %2 Days Up To Date',
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Get condition subfilter type. Can be used in parent level queries
     *
     * @return string
     */
    public function getSubfilterType()
    {
        return 'date';
    }

    /**
     * Apply date subfilter to parent/base condition query
     *
     * @param string $fieldName base query field name
     * @param bool $requireValid strict validation flag
     * @param int|Zend_Db_Expr $website
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSubfilterSql($fieldName, $requireValid, $website)
    {
        $value = $this->getValue();
        if (!$value || !is_numeric($value)) {
            return false;
        }

        $limit = date('Y-m-d', strtotime("now -{$value} days"));
        //$operator = (($requireValid && $this->getOperator() == '==') ? '>' : '<');
        $operator = $this->getOperator();
        return sprintf("%s %s '%s'", $fieldName, $operator, $limit);
    }

    /**
     * @param int $customer
     * @param int $website
     * @param array $params
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isSatisfiedBy($customer, $website, $params)
    {
        $validationRequired = $params['validation_required'];
        $quoteItemId = $params['quote_item']['item_id'];
        $select = $this->getResource()->createSelect();
        $select->from(
            ['item' => $this->getResource()->getTable('quote_item')],
            [new \Zend_Db_Expr(1)]
        )->where(
            'item.item_id = ?',
            $quoteItemId
        )->where(
            $this->getSubfilterSql('item.created_at', $validationRequired, null)
        )->limit(1);
        $result = $this->quoteResource->getConnection()->fetchOne($select);
        return $result > 0;
    }

    /**
     * @param int $websiteId
     * @param null $requireValid
     * @return array
     */
    public function getSatisfiedIds($websiteId)
    {
        $result = [];
        $select = $this->quoteResource->getConnection()->select();
        $subFilter = $this->getSubfilterSql('item.created_at', null, $websiteId);
        $select->from(
            ['item' => $this->getResource()->getTable('quote_item')],
            ['quote_id']
        );
        $conditions = "item.quote_id = list.entity_id";
        $select->joinInner(
            ['list' => $this->getResource()->getTable('quote')],
            $conditions,
            []
        );
        $select->where('list.is_active = ?', new \Zend_Db_Expr(1));
        $select->where($subFilter);
        if (isset($select)) {
            $result = $this->quoteResource->getConnection()->fetchCol($select);
        }
        return $result;
    }
}
