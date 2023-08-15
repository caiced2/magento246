<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit;

/**
 * @api
 * @since 100.0.2
 */
class Js extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize current rma
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        if ($this->_coreRegistry->registry('current_rma')) {
            $this->setRmaId($this->_coreRegistry->registry('current_rma')->getId());
        }
    }

    /**
     * Get url for Details AJAX Action
     *
     * @return string
     */
    public function getLoadAttributesUrl()
    {
        return $this->getUrl(
            'adminhtml/*/loadAttributes',
            ['id' => $this->_coreRegistry->registry('current_rma')->getId()]
        );
    }

    /**
     * Get url for Split Line AJAX Action
     *
     * @return string
     */
    public function getLoadSplitLineUrl()
    {
        return $this->getUrl(
            'adminhtml/*/loadSplitLine',
            ['id' => $this->_coreRegistry->registry('current_rma')->getId()]
        );
    }

    /**
     * Get url for Shipping Methods Action
     *
     * @return string
     */
    public function getLoadShippingMethodsUrl()
    {
        return $this->getUrl(
            'adminhtml/*/showShippingMethods',
            ['id' => $this->_coreRegistry->registry('current_rma')->getId()]
        );
    }

    /**
     * Get url for Psl Action
     *
     * @return string
     */
    public function getLoadPslUrl()
    {
        return $this->getUrl('adminhtml/*/psl', ['id' => $this->_coreRegistry->registry('current_rma')->getId()]);
    }
}
