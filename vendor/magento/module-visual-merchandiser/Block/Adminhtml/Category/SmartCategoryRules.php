<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Block\Adminhtml\Category;

/**
 * @api
 * @since 100.0.2
 */
class SmartCategoryRules extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    protected $_rules;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\VisualMerchandiser\Model\Rules $rules,
        \Magento\Framework\Registry $registry,
        $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_rules = $rules;
        parent::__construct($context, $data);
    }

    /**
     * Get smart category attribute value for the current category
     *
     * @return string
     */
    public function getSmartCategoryRules()
    {
        $category =  $this->_coreRegistry->registry('current_category');
        $rules = $this->_rules->loadByCategory($category);
        return $this->escapeHtml($rules->getConditionsSerialized());
    }

    /**
     * Is smart category visible
     *
     * @return bool
     */
    public function isSmartCategoryVisible()
    {
        $category =  $this->_coreRegistry->registry('current_category');
        if ($category->getId()) {
            return true;
        }
        return false;
    }
}
