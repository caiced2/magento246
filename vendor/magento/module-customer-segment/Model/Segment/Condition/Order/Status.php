<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Order;

use Magento\CustomerSegment\Model\Condition\AbstractCondition;

/**
 * Order status condition
 */
class Status extends AbstractCondition
{
    /**
     * Any option value
     */
    const VALUE_ANY = 'any';

    /**
     * @var string
     */
    protected $_inputType = 'select';

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Eav\Model\Config $eavConfig,
        array $data = []
    ) {
        $this->_orderConfig = $orderConfig;
        $this->_eavConfig = $eavConfig;
        parent::__construct($context, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Order\Status::class);
        $this->setValue(null);
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return string[]
     */
    public function getMatchedEvents()
    {
        return ['sales_order_save_commit_after'];
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return ['value' => $this->getType(), 'label' => __('Order Status')];
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
     * Init value select options
     *
     * @return $this
     */
    public function loadValueOptions()
    {
        $this->setValueOption(array_merge([self::VALUE_ANY => __('Any')], $this->_orderConfig->getStatuses()));
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
            'Order Status %1 %2:',
            $this->getOperatorElementHtml(),
            $this->getValueElementHtml()
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Get order status attribute object
     *
     * @return \Magento\Eav\Model\Entity\Attribute
     */
    public function getAttributeObject()
    {
        return $this->_eavConfig->getAttribute('order', 'status');
    }

    /**
     * Used subfilter type
     *
     * @return string
     */
    public function getSubfilterType()
    {
        return 'order';
    }

    /**
     * Apply status subfilter to parent/base condition query
     *
     * @param string $fieldName base query field name
     * @param bool $requireValid strict validation flag
     * @param int|\Zend_Db_Expr $website
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSubfilterSql($fieldName, $requireValid, $website)
    {
        if ($this->getValue() == self::VALUE_ANY) {
            return '';
        }
        return $this->getResource()->createConditionSql($fieldName, $this->getOperator(), $this->getValue());
    }
}
