<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition;

use Magento\CustomerSegment\Model\Condition\AbstractCondition;

/**
 * Date range combo
 *
 * @method \Magento\CustomerSegment\Model\Segment\Condition\Daterange setType(string $type)
 * @method \Magento\CustomerSegment\Model\Segment\Condition\Daterange setValue(string $value)
 */
class Daterange extends AbstractCondition
{
    /**
     * Input type for operator options
     *
     * @var string
     */
    protected $_inputType = 'select';

    /**
     * Value form element
     *
     * @var \Magento\Framework\Data\Form\Element\Text
     */
    private $_valueElement = null;

    /**
     * Adminhtml data
     *
     * @var \Magento\Backend\Helper\Data
     */
    protected $_adminhtmlData = null;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    private $quoteResource;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Backend\Helper\Data $adminhtmlData
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Backend\Helper\Data $adminhtmlData,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        array $data = []
    ) {
        $this->_adminhtmlData = $adminhtmlData;
        parent::__construct($context, $resourceSegment, $data);

        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Daterange::class);
        $this->setValue(null);
        $this->quoteResource = $quoteResource;
    }

    /**
     * Inherited hierarchy options getter
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return ['value' => $this->getType(), 'label' => __('Date Range')];
    }

    /**
     * Value element type getter
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Enable chooser selection button
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getExplicitApply()
    {
        return true;
    }

    /**
     * Avoid value distortion by possible options
     *
     * @return array
     */
    public function getValueSelectOptions()
    {
        return [];
    }

    /**
     * Chooser button HTML getter
     *
     * @return string
     */
    public function getValueAfterElementHtml()
    {
        return '<a href="javascript:void(0)" class="rule-chooser-trigger"><img src="' .
            $this->_assetRepo->getUrl(
                'images/rule_chooser_trigger.gif'
            ) . '" alt="" class="v-middle rule-chooser-trigger"' . 'title="' . __(
                'Open Chooser'
            ) . '" /></a>';
    }

    /**
     * Chooser URL getter
     *
     * @return string
     */
    public function getValueElementChooserUrl()
    {
        return $this->_adminhtmlData->getUrl(
            'customersegment/index/chooserDaterange',
            ['value_element_id' => $this->_valueElement->getId()]
        );
    }

    /**
     * Render as HTML
     *
     * Chooser div is declared in such a way, that element value will be treated as is
     *
     * @return string
     */
    public function asHtml()
    {
        $this->_valueElement = $this->getValueElement();
        return $this->getTypeElementHtml() . __(
            'Date Range %1 within %2',
            $this->getOperatorElementHtml(),
            $this->_valueElement->getHtml()
        ) .
            $this->getRemoveLinkHtml() .
            '<div class="rule-chooser no-split" url="' .
            $this->getValueElementChooserUrl() .
            '"></div>';
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
     * @param int|\Zend_Db_Expr $website
     * @return string|false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSubfilterSql($fieldName, $requireValid, $website)
    {
        $value = explode('...', $this->getValue());
        if (!isset($value[0]) || !isset($value[1])) {
            return false;
        }

        $regexp = '#^\d{4}-\d{2}-\d{2}$#';
        if (!preg_match($regexp, $value[0]) || !preg_match($regexp, $value[1])) {
            return false;
        }

        $start = $value[0] . ' 00:00:00';
        $end = $value[1] . ' 23:59:59';

        if (!$start || !$end) {
            return false;
        }

        $inOperator = $requireValid && $this->getOperator() == '==' ? 'BETWEEN' : 'NOT BETWEEN';
        return sprintf("%s %s '%s' AND '%s'", $fieldName, $inOperator, $start, $end);
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
        $quoteItemId = $params['quote_item']['item_id'];
        $validationRequired = $params['validation_required'];
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
        $subFilter = $this->getSubfilterSql('item.created_at', true, $websiteId);
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
