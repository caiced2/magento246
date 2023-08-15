<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Block\Adminhtml\Customer\Formtype;

/**
 * Create New Form Type Block
 *
 * @api
 * @since 100.0.2
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current form type instance
     *
     * @return \Magento\Eav\Model\Form\Type
     */
    protected function _getFormType()
    {
        return $this->_coreRegistry->registry('current_form_type');
    }

    /**
     * Initialize Form Container
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'type_id';
        $this->_blockGroup = 'Magento_CustomerCustomAttributes';
        $this->_controller = 'adminhtml_customer_formtype';

        parent::_construct();

        $editMode = $this->_coreRegistry->registry('edit_mode');
        if ($editMode == 'edit') {
            $this->buttonList->update('save', 'onclick', 'formType.save(false)');
            $this->buttonList->update('save', 'data_attribute', null);
            $this->buttonList->add(
                'save_and_edit_button',
                ['label' => __('Save and Continue Edit'), 'onclick' => 'formType.save(true)', 'class' => 'save']
            );

            if ($this->_getFormType()->getIsSystem()) {
                $this->buttonList->remove('delete');
            }

            $this->_headerText = __('Edit Form Type "%1"', $this->_getFormType()->getCode());
        } else {
            $this->_headerText = __('New Form Type');
        }
    }
}
