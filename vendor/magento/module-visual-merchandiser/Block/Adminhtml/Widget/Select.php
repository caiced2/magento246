<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Block\Adminhtml\Widget;

abstract class Select extends \Magento\Backend\Block\Widget
{
    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    protected $_rules;

    /**
     * @var \Magento\VisualMerchandiser\Model\Sorting
     */
    protected $_sorting;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     * @param \Magento\VisualMerchandiser\Model\Sorting $sorting
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\VisualMerchandiser\Model\Rules $rules,
        \Magento\VisualMerchandiser\Model\Sorting $sorting,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_rules = $rules;
        $this->_sorting = $sorting;
        $this->_registry = $registry;
    }

    /**
     * Define block template
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('widget/select.phtml');
    }

    /**
     * @return array
     */
    abstract public function getSelectOptions();

    /**
     * Get current value
     *
     * @return string
     */
    public function getSelectValue()
    {
        return "";
    }
}
