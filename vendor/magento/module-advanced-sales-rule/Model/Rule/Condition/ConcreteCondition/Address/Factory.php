<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedRule\Model\Condition\FilterInterface;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface;

class Factory
{
    /**
     * @deprecated
     * @codingStandardsIgnoreStart
     */
    const NAMESPACE_STRING = 'Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address';
    // @codingStandardsIgnoreEnd

    // @codingStandardsIgnoreEnd
    protected $objectManager;

    /**
     * @var array
     */
    protected $filterableAttributes = [
        'payment_method' => 'PaymentMethod',
        'shipping_method' => 'ShippingMethod',
        'country_id' => 'CountryId',
        'region_id' => 'RegionId',
        'postcode' => 'Postcode',
        'region' => 'Region',
    ];

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
     * @param \Magento\AdvancedSalesRule\Model\Rule\Condition\Address $addressCondition
     * @return \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
     */
    public function create($addressCondition)
    {
        $attribute = $addressCondition->getAttribute();

        if (isset($this->filterableAttributes[$attribute])) {
            $fullClassName = __NAMESPACE__ . '\\' . $this->filterableAttributes[$attribute];
            $concreteCondition = $this->objectManager->create(
                $fullClassName,
                [
                    'condition' => $addressCondition,
                ]
            );
            return $concreteCondition;
        } else {
            return $this->objectManager->create(
                \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\DefaultCondition::class
            );
        }
    }
}
