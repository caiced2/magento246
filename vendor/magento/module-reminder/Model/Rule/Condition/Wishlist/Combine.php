<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Wishlist;

/**
 * Rule conditions container
 */
class Combine extends \Magento\Reminder\Model\Condition\Combine\AbstractCombine
{
    /**
     * Wishlist Sharing Factory
     *
     * @var \Magento\Reminder\Model\Rule\Condition\Wishlist\SharingFactory
     */
    protected $_sharingFactory;

    /**
     * Wishlist Quantity Factory
     *
     * @var \Magento\Reminder\Model\Rule\Condition\Wishlist\QuantityFactory
     */
    protected $_quantityFactory;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Reminder\Model\Rule\Condition\Wishlist\SharingFactory $sharingFactory
     * @param \Magento\Reminder\Model\Rule\Condition\Wishlist\QuantityFactory $quantityFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\SharingFactory $sharingFactory,
        \Magento\Reminder\Model\Rule\Condition\Wishlist\QuantityFactory $quantityFactory,
        array $data = []
    ) {
        parent::__construct($context, $ruleResource, $data);
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Wishlist\Combine::class);
        $this->_sharingFactory = $sharingFactory;
        $this->_quantityFactory = $quantityFactory;
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return array_merge_recursive(
            parent::getNewChildSelectOptions(),
            [
                $this->_getRecursiveChildSelectOption(),
                $this->_sharingFactory->create()->getNewChildSelectOptions(),
                $this->_quantityFactory->create()->getNewChildSelectOptions(),
                [ // subselection combo
                    'value' => \Magento\Reminder\Model\Rule\Condition\Wishlist\Subselection::class,
                    'label' => __('Items Subselection')
                ]
            ]
        );
    }
}
