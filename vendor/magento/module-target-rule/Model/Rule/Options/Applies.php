<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Rule\Options;

/**
 * Statuses option array
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Applies implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Target Rule model
     *
     * @var \Magento\TargetRule\Model\Rule
     */
    protected $_targetRuleModel;

    /**
     * @param \Magento\TargetRule\Model\Rule $targetRuleModel
     */
    public function __construct(\Magento\TargetRule\Model\Rule $targetRuleModel)
    {
        $this->_targetRuleModel = $targetRuleModel;
    }

    /**
     * Return statuses array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_targetRuleModel->getAppliesToOptions();
    }
}
