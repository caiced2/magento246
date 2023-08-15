<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface;

class Factory
{
    /**
     * Object Manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Magento\AdvancedSalesRule\Model\Rule\Condition\Product $productCondition
     * @return \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
     */
    public function create($productCondition)
    {
        $operator = $productCondition->getOperator();
        $attribute = $productCondition->getAttribute();

        //quote attributes are not filterable at this time
        $quoteAttributes = [
            'quote_item_qty',
            'quote_item_price',
            'quote_item_row_total',
        ];

        if ($attribute == 'category_ids') {
            $categories = $productCondition->getValueParsed();
            $concreteCondition = $this->objectManager->create(
                \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Categories::class,
                [
                    'data' => [
                        'operator' => $operator,
                        'categories' => $categories,
                    ]
                ]
            );
            return $concreteCondition;
        } elseif (in_array($attribute, $quoteAttributes)) {
            return $this->objectManager->create(
                \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition::class
            );
        } else {
            return $this->objectManager->create(
                \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Attribute::class,
                [
                    'condition' => $productCondition,
                ]
            );
        }
    }
}
