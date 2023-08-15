<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule;

/**
 * Factory class for Rule Condition
 */
class ConditionFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Available conditions
     *
     * @var string[]
     */
    protected $_conditions = [
        \Magento\Reminder\Model\Rule\Condition\Cart\Amount::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Attributes::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Combine::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Couponcode::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Itemsquantity::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Sku::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Storeview::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Subcombine::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Subselection::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Totalquantity::class,
        \Magento\Reminder\Model\Rule\Condition\Cart\Virtual::class,
        \Magento\Reminder\Model\Rule\Condition\Combine\Root::class,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\Attributes::class,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\Combine::class,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\Quantity::class,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\Sharing::class,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\Storeview::class,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\Subcombine::class,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\Subselection::class,
        \Magento\Reminder\Model\Rule\Condition\Cart::class,
        \Magento\Reminder\Model\Rule\Condition\Combine::class,
        \Magento\Reminder\Model\Rule\Condition\Wishlist::class,
    ];

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param string $type
     * @return \Magento\Rule\Model\Condition\AbstractCondition
     * @throws \InvalidArgumentException
     */
    public function create($type)
    {
        if (in_array($type, $this->_conditions)) {
            return $this->_objectManager->create($type);
        } else {
            throw new \InvalidArgumentException(__('Condition type is unexpected'));
        }
    }
}
