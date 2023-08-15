<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Block\Adminhtml\Customer\Formtype\Edit\Tab;

/**
 * Form Type Edit General Tab Block
 *
 * @api
 * @since 100.0.2
 */
class General extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Framework\View\Design\Theme\LabelFactory
     */
    protected $_themeLabelFactory;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\View\Design\Theme\LabelFactory $themeLabelFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\View\Design\Theme\LabelFactory $themeLabelFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_themeLabelFactory = $themeLabelFactory;
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Initialize Edit Form
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setDestElementId('edit_form');
        $this->setShowGlobalIcon(false);
        parent::_construct();
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /* @var $model \Magento\Eav\Model\Form\Type */
        $model = $this->_coreRegistry->registry('current_form_type');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $fieldset = $form->addFieldset('general_fieldset', ['legend' => __('General Information')]);

        $fieldset->addField('continue_edit', 'hidden', ['name' => 'continue_edit', 'value' => 0]);
        $fieldset->addField('type_id', 'hidden', ['name' => 'type_id', 'value' => $model->getId()]);

        $fieldset->addField('form_type_data', 'hidden', ['name' => 'form_type_data']);

        $fieldset->addField(
            'code',
            'text',
            [
                'name' => 'code',
                'label' => __('Form Code'),
                'title' => __('Form Code'),
                'required' => true,
                'class' => 'validate-code',
                'disabled' => true,
                'value' => $model->getCode()
            ]
        );

        $fieldset->addField(
            'label',
            'text',
            [
                'name' => 'label',
                'label' => __('Form Title'),
                'title' => __('Form Title'),
                'required' => true,
                'value' => $model->getLabel()
            ]
        );

        /** @var $label \Magento\Framework\View\Design\Theme\Label */
        $label = $this->_themeLabelFactory->create();
        $options = $label->getLabelsCollection();
        array_unshift($options, ['label' => __('All Themes'), 'value' => '']);
        $fieldset->addField(
            'theme',
            'select',
            [
                'name' => 'theme',
                'label' => __('For Theme'),
                'title' => __('For Theme'),
                'values' => $options,
                'value' => $model->getTheme(),
                'disabled' => true
            ]
        );

        $fieldset->addField(
            'store_id',
            'select',
            [
                'name' => 'store_id',
                'label' => __('Store View'),
                'title' => __('Store View'),
                'values' => $this->_systemStore->getStoreValuesForForm(false, true),
                'value' => $model->getStoreId(),
                'disabled' => true
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Retrieve Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('General');
    }

    /**
     * Retrieve Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('General');
    }

    /**
     * Check is can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check tab is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
