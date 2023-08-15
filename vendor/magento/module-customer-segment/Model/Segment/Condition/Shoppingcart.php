<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Shopping cart conditions options group
 */
namespace Magento\CustomerSegment\Model\Segment\Condition;

class Shoppingcart extends \Magento\CustomerSegment\Model\Condition\AbstractCondition
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
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Shoppingcart::class);
        $this->setValue(null);
    }

    /**
     * Get condition "selectors" for parent block
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return [
            'value' => [
                $this->_conditionFactory->create('Shoppingcart\Amount')->getNewChildSelectOptions(),
                $this->_conditionFactory->create('Shoppingcart\Itemsquantity')->getNewChildSelectOptions(),
                $this->_conditionFactory->create('Shoppingcart\Productsquantity')->getNewChildSelectOptions(),
            ],
            'label' => __('Shopping Cart'),
            'available_in_guest_mode' => true
        ];
    }
}
