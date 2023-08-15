<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Adminhtml\Banner\Edit\Tab;

/**
 * Main banner properties edit form
 *
 * @api
 * @author     Magento Core Team <core@magentocommerce.com>
 * @deprecated Banner form configuration has been moved on ui component declaration
 * @see app/code/Magento/Banner/view/adminhtml/ui_component/banner_form.xml
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Properties extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Banner config
     *
     * @var \Magento\Banner\Model\Config
     */
    protected $_bannerConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Banner\Model\Config $bannerConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Banner\Model\Config $bannerConfig,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_bannerConfig = $bannerConfig;
    }

    /**
     * Set form id prefix, declare fields for banner properties
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $htmlIdPrefix = 'banner_properties_';
        $form->setHtmlIdPrefix($htmlIdPrefix);

        $model = $this->_coreRegistry->registry('current_banner');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Dynamic Block Properties')]);

        if ($model->getBannerId()) {
            $fieldset->addField('banner_id', 'hidden', ['name' => 'banner_id']);
        }

        $fieldset->addField(
            'name',
            'text',
            [
                'label' => __('Dynamic Block Name'),
                'name' => 'name',
                'required' => true,
                'disabled' => (bool)$model->getIsReadonly()
            ]
        );

        $fieldset->addField(
            'is_enabled',
            'select',
            [
                'label' => __('Status'),
                'name' => 'is_enabled',
                'required' => true,
                'disabled' => (bool)$model->getIsReadonly(),
                'options' => [
                    \Magento\Banner\Model\Banner::STATUS_ENABLED => __('Active'),
                    \Magento\Banner\Model\Banner::STATUS_DISABLED => __('Inactive'),
                ]
            ]
        );
        if (!$model->getId()) {
            $model->setData('is_enabled', \Magento\Banner\Model\Banner::STATUS_ENABLED);
        }

        // whether to specify banner types - for UI design purposes only
        $fieldset->addField(
            'is_types',
            'select',
            [
                'label' => __('Applies To'),
                'options' => ['0' => __('Any Dynamic Block Type'), '1' => __('Specified Dynamic Block Types')],
                'disabled' => (bool)$model->getIsReadonly()
            ]
        );
        $model->setIsTypes((string)(int)$model->getTypes());
        // see $form->setValues() below

        $fieldset->addField(
            'types',
            'multiselect',
            [
                'label' => __('Specify Types'),
                'name' => 'types',
                'disabled' => (bool)$model->getIsReadonly(),
                'values' => $this->_bannerConfig->toOptionArray(false, false),
                'can_be_empty' => true
            ]
        );

        $afterFormBlock = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Form\Element\Dependence::class
        )->addFieldMap(
            "{$htmlIdPrefix}is_types",
            'is_types'
        )->addFieldMap(
            "{$htmlIdPrefix}types",
            'types'
        )->addFieldDependence(
            'types',
            'is_types',
            '1'
        );

        $this->_eventManager->dispatch(
            'banner_edit_tab_properties_after_prepare_form',
            ['model' => $model, 'form' => $form, 'block' => $this, 'after_form_block' => $afterFormBlock]
        );

        $this->setChild('form_after', $afterFormBlock);

        $form->setValues($model->getData());
        $this->setForm($form);

        return $this;
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Dynamic Block Properties');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Returns status flag about this tab can be showen or not
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
