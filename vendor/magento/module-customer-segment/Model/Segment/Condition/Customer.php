<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition;

use Magento\CustomerSegment\Model\Condition\AbstractCondition;

/**
 * Customer conditions options group
 */
class Customer extends AbstractCondition
{
    /**
     * @var \Magento\CustomerSegment\Model\ConditionFactory
     */
    protected $_conditionFactory;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        array $data = []
    ) {
        $this->_conditionFactory = $conditionFactory;
        parent::__construct($context, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Customer::class);
        $this->setValue(null);
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $conditions = $this->_conditionFactory->create('Customer\Attributes')->getNewChildSelectOptions();
        $conditions = array_merge(
            $conditions,
            $this->_conditionFactory->create('Customer\Newsletter')->getNewChildSelectOptions()
        );
        $conditions = array_merge(
            $conditions,
            $this->_conditionFactory->create('Customer\Storecredit')->getNewChildSelectOptions()
        );
        return ['value' => $conditions, 'label' => __('Customer')];
    }
}
