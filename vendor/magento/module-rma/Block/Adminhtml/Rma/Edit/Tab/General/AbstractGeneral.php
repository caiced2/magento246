<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General;

/**
 * Abstract Fieldset block for RMA view
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class AbstractGeneral extends \Magento\Backend\Block\Widget\Form
{
    /**
     * Form, created in parent block
     *
     * @var \Magento\Framework\Data\Form
     */
    protected $_parentForm = null;

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
     * Get Form Object Which is Parent to this block
     *
     * @return null|\Magento\Framework\Data\Form
     */
    public function getParentForm()
    {
        if ($this->_parentForm === null && $this->getParentBlock()) {
            $this->_parentForm = $this->getParentBlock()->getForm();
        }
        return $this->_parentForm;
    }

    /**
     * Add specific fieldset block to parent block form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_rma');
        $form = $this->getParentForm();

        $this->_addFieldset();

        if ($form && $model) {
            $form->setValues($model->getData());
        }
        if ($form) {
            $this->setForm($form);
        }

        return $this;
    }

    /**
     * Add fieldset with required fields
     *
     * @return void
     */
    protected function _addFieldset()
    {
    }

    /**
     * Getter of model's data
     *
     * @param string $field
     * @return mixed|null
     */
    public function getRmaData($field)
    {
        $model = $this->_coreRegistry->registry('current_rma');
        if ($model) {
            return $model->getData($field);
        } else {
            return null;
        }
    }

    /**
     * Get Order, RMA Attached to
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Get Customer Name (billing name)
     *
     * @return string
     */
    public function getCustomerName()
    {
        return $this->escapeHtml($this->getOrder()->getCustomerName());
    }
}
