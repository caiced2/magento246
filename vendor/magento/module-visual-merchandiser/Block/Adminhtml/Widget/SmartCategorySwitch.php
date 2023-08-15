<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Block\Adminhtml\Widget;

/**
 * @api
 * @since 100.0.2
 */
class SmartCategorySwitch extends \Magento\Backend\Block\Widget
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    protected $rules;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\VisualMerchandiser\Model\Rules $rules,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->rules = $rules;
    }

    /**
     * @return bool
     */
    public function smartCategoryEnabled()
    {
        $category = $this->registry->registry('current_category');
        if ($category) {
            $rules = $this->rules->loadByCategory($category);
            return (bool) $rules->getIsActive();
        }
        return false;
    }
}
