<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Orders conditions options group
 */
namespace Magento\CustomerSegment\Model\Segment\Condition;

class Sales extends \Magento\CustomerSegment\Model\Condition\AbstractCondition
{
    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        array $data = []
    ) {
        parent::__construct($context, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Sales::class);
        $this->setValue(null);
    }

    /**
     * Get condition "selectors"
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return [
            'value' => [
                [
                     // order address combo
                    'value' => \Magento\CustomerSegment\Model\Segment\Condition\Order\Address::class,
                    'label' => __('Order Address'),
                ],
                [
                    'value' => \Magento\CustomerSegment\Model\Segment\Condition\Sales\Salesamount::class,
                    'label' => __('Sales Amount')
                ],
                [
                    'value' => \Magento\CustomerSegment\Model\Segment\Condition\Sales\Ordersnumber::class,
                    'label' => __('Number of Orders')
                ],
                [
                    'value' => \Magento\CustomerSegment\Model\Segment\Condition\Sales\Purchasedquantity::class,
                    'label' => __('Purchased Quantity')
                ],
            ],
            'label' => __('Sales')
        ];
    }
}
