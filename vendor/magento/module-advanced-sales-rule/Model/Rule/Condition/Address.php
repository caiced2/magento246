<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Model\Rule\Condition;

use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use \Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Address\Factory as AddressConditionFactory;

class Address extends \Magento\SalesRule\Model\Rule\Condition\Address implements
    \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
{
    /**
     * @var AddressConditionFactory
     */
    protected $concreteConditionFactory;

    /**
     * @var \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
     */
    protected $concreteCondition = null;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Directory\Model\Config\Source\Country $directoryCountry
     * @param \Magento\Directory\Model\Config\Source\Allregion $directoryAllregion
     * @param \Magento\Shipping\Model\Config\Source\Allmethods $shippingAllmethods
     * @param \Magento\Payment\Model\Config\Source\Allmethods $paymentAllmethods
     * @param AddressConditionFactory $concreteConditionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Directory\Model\Config\Source\Country $directoryCountry,
        \Magento\Directory\Model\Config\Source\Allregion $directoryAllregion,
        \Magento\Shipping\Model\Config\Source\Allmethods $shippingAllmethods,
        \Magento\Payment\Model\Config\Source\Allmethods $paymentAllmethods,
        AddressConditionFactory $concreteConditionFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $directoryCountry,
            $directoryAllregion,
            $shippingAllmethods,
            $paymentAllmethods,
            $data
        );
        $this->concreteConditionFactory = $concreteConditionFactory;
    }

    /**
     * Whether this condition can be filtered using index table
     *
     * @return bool
     */
    public function isFilterable()
    {
        if ($this->concreteCondition === null) {
            $this->concreteCondition = $this->concreteConditionFactory->create($this);
        }
        return $this->concreteCondition->isFilterable();
    }

    /**
     * Return a list of filter groups that represent this condition
     *
     * @return FilterGroupInterface[]
     */
    public function getFilterGroups()
    {
        if ($this->concreteCondition === null) {
            $this->concreteCondition = $this->concreteConditionFactory->create($this);
        }
        return $this->concreteCondition->getFilterGroups();
    }
}
