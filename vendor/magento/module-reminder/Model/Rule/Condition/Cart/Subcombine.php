<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Cart;

/**
 * Rule conditions cart items subselection container
 */
class Subcombine extends \Magento\Reminder\Model\Condition\Combine\AbstractCombine
{
    /**
     * Cart Storeview Factory
     *
     * @var \Magento\Reminder\Model\Rule\Condition\Cart\StoreviewFactory
     */
    protected $_storeviewFactory;

    /**
     * Cart Sku Factory
     *
     * @var \Magento\Reminder\Model\Rule\Condition\Cart\SkuFactory
     */
    protected $_skuFactory;

    /**
     * Cart Attributes Factory
     *
     * @var \Magento\Reminder\Model\Rule\Condition\Cart\AttributesFactory
     */
    protected $_attrFactory;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param \Magento\Reminder\Model\Rule\Condition\Cart\StoreviewFactory $storeviewFactory
     * @param \Magento\Reminder\Model\Rule\Condition\Cart\SkuFactory $skuFactory
     * @param \Magento\Reminder\Model\Rule\Condition\Cart\AttributesFactory $attrFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        \Magento\Reminder\Model\Rule\Condition\Cart\StoreviewFactory $storeviewFactory,
        \Magento\Reminder\Model\Rule\Condition\Cart\SkuFactory $skuFactory,
        \Magento\Reminder\Model\Rule\Condition\Cart\AttributesFactory $attrFactory,
        array $data = []
    ) {
        parent::__construct($context, $ruleResource, $data);
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Cart\Subcombine::class);
        $this->_storeviewFactory = $storeviewFactory;
        $this->_skuFactory = $skuFactory;
        $this->_attrFactory = $attrFactory;
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
                $this->_storeviewFactory->create()->getNewChildSelectOptions(),
                $this->_skuFactory->create()->getNewChildSelectOptions(),
                $this->_attrFactory->create()->getNewChildSelectOptions()
            ]
        );
    }
}
