<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Block\Adminhtml\Rma\Item\Attribute\Edit\Tab;

/**
 * RMA Item Attributes Edit Form
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Main extends \Magento\Eav\Block\Adminhtml\Attribute\Edit\Main\AbstractMain implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Rma eav
     *
     * @var \Magento\CustomAttributeManagement\Helper\Data
     */
    protected $_attributeHelper = null;

    /**
     * @var \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension
     */
    private $extensionValidator;

    /**
     * @var \Magento\Rma\Helper\Eav
     */
    private $_rmaEav;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Eav\Helper\Data $eavData
     * @param \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory
     * @param \Magento\Eav\Model\Adminhtml\System\Config\Source\InputtypeFactory $inputTypeFactory
     * @param \Magento\Eav\Block\Adminhtml\Attribute\PropertyLocker $propertyLocker
     * @param \Magento\CustomAttributeManagement\Helper\Data $attributeHelper
     * @param \Magento\Rma\Helper\Eav $rmaEav
     * @param array $data
     * @param \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension|null $extensionValidator
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Eav\Helper\Data $eavData,
        \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory,
        \Magento\Eav\Model\Adminhtml\System\Config\Source\InputtypeFactory $inputTypeFactory,
        \Magento\Eav\Block\Adminhtml\Attribute\PropertyLocker $propertyLocker,
        \Magento\CustomAttributeManagement\Helper\Data $attributeHelper,
        \Magento\Rma\Helper\Eav $rmaEav,
        array $data = [],
        \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $extensionValidator = null
    ) {
        $this->_attributeHelper = $attributeHelper;
        $this->_rmaEav = $rmaEav;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $eavData,
            $yesnoFactory,
            $inputTypeFactory,
            $propertyLocker,
            $data
        );
        $this->extensionValidator = $extensionValidator
            ?: \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\MediaStorage\Model\File\Validator\NotProtectedExtension::class);
    }

    /**
     * Preparing global layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        $result = parent::_prepareLayout();
        $renderer = $this->getLayout()->getBlock('fieldset_element_renderer');
        if ($renderer instanceof \Magento\Framework\Data\Form\Element\Renderer\RendererInterface) {
            \Magento\Framework\Data\Form::setFieldsetElementRenderer($renderer);
        }

        return $result;
    }

    /**
     * Adding customer form elements for edit form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();

        $attribute = $this->getAttributeObject();
        $form = $this->getForm();
        $fieldset = $form->getElement('base_fieldset');

        $fieldset->removeField('frontend_class');
        $fieldset->removeField('is_unique');

        // update Input Types
        $element = $form->getElement('frontend_input');
        $element->setValues($this->getAttributeInputOptions());
        $element->setLabel(__('Input Type'));
        $element->setRequired(true);

        // add limitation to attribute code
        // customer attribute code can have prefix "rma_item_" and its length must be max length minus prefix length
        $element = $form->getElement('attribute_code');
        $element->setNote(
            __(
                'This is used internally. Make sure you don\'t use spaces or more than %1 symbols.',
                \Magento\Eav\Model\Entity\Attribute::ATTRIBUTE_CODE_MAX_LENGTH
            )
        );

        $fieldset->addField(
            'multiline_count',
            'text',
            [
                'name' => 'multiline_count',
                'label' => __('Lines Count'),
                'title' => __('Lines Count'),
                'required' => true,
                'class' => 'validate-digits-range digits-range-2-20',
                'note' => __('Valid range 2-20')
            ],
            'frontend_input'
        );

        $fieldset->addField(
            'input_validation',
            'select',
            [
                'name' => 'input_validation',
                'label' => __('Input Validation'),
                'title' => __('Input Validation'),
                'values' => ['' => __('None')]
            ],
            'default_value_textarea'
        );

        $fieldset->addField(
            'min_text_length',
            'text',
            [
                'name' => 'min_text_length',
                'label' => __('Minimum Text Length'),
                'title' => __('Minimum Text Length'),
                'class' => 'validate-digits'
            ],
            'input_validation'
        );

        $fieldset->addField(
            'max_text_length',
            'text',
            [
                'name' => 'max_text_length',
                'label' => __('Maximum Text Length'),
                'title' => __('Maximum Text Length'),
                'class' => 'validate-digits'
            ],
            'min_text_length'
        );

        $fieldset->addField(
            'max_file_size',
            'text',
            [
                'name' => 'max_file_size',
                'label' => __('Maximum File Size (bytes)'),
                'title' => __('Maximum File Size (bytes)'),
                'class' => 'validate-digits'
            ],
            'max_text_length'
        );

        $forbiddenFileExtensions = implode(',', $this->extensionValidator->getProtectedFileExtensions());
        $fieldset->addField(
            'file_extensions',
            'text',
            [
                'name' => 'file_extensions',
                'label' => __('File Extensions'),
                'title' => __('File Extensions'),
                'note' => __('Comma separated. Forbidden file extensions: %1.', $forbiddenFileExtensions),
                'class' => 'validate-forbidden-extensions',
                'data-validation-params' => $forbiddenFileExtensions,
            ],
            'max_file_size'
        );

        $fieldset->addField(
            'max_image_width',
            'text',
            [
                'name' => 'max_image_width',
                'label' => __('Maximum Image Width (px)'),
                'title' => __('Maximum Image Width (px)'),
                'class' => 'validate-digits'
            ],
            'file_extensions'
        );

        $fieldset->addField(
            'max_image_heght',
            'text',
            [
                'name' => 'max_image_heght',
                'label' => __('Maximum Image Height (px)'),
                'title' => __('Maximum Image Height (px)'),
                'class' => 'validate-digits'
            ],
            'max_image_width'
        );

        $fieldset->addField(
            'input_filter',
            'select',
            [
                'name' => 'input_filter',
                'label' => __('Input/Output Filter'),
                'title' => __('Input/Output Filter'),
                'values' => ['' => __('None')]
            ]
        );

        /** @var $config \Magento\Config\Model\Config\Source\Yesno */
        $config = $this->_yesnoFactory->create();
        $yesnoSource = $config->toOptionArray();

        $fieldset = $form->addFieldset('front_fieldset', ['legend' => __('Storefront Properties')]);

        $fieldset->addField(
            'is_visible',
            'select',
            [
                'name' => 'is_visible',
                'label' => __('Show on Storefront'),
                'title' => __('Show on Storefront'),
                'values' => $yesnoSource
            ]
        );

        $fieldset->addField(
            'sort_order',
            'text',
            [
                'name' => 'sort_order',
                'label' => __('Sort Order'),
                'title' => __('Sort Order'),
                'required' => true,
                'class' => 'validate-digits'
            ]
        );

        $fieldset->addField(
            'used_in_forms',
            'multiselect',
            [
                'name' => 'used_in_forms',
                'label' => __('Forms to Use In'),
                'title' => __('Forms to Use In'),
                'values' => $this->_attributeHelper->getAttributeFormOptions(),
                'value' => $attribute->getUsedInForms(),
                'can_be_empty' => true,
                'required' => true,
            ]
        )->setSize(
            5
        );

        if ($attribute->getId()) {
            $elements = [];
            if ($attribute->getIsSystem()) {
                $elements = ['sort_order', 'is_visible', 'is_required'];
            }
            if (!$attribute->getIsUserDefined() && !$attribute->getIsSystem()) {
                $elements = ['sort_order'];
            }
            foreach ($elements as $elementId) {
                $form->getElement($elementId)->setDisabled(true);
            }

            $inputTypeProp = $this->_attributeHelper->getAttributeInputTypes($attribute->getFrontendInput());

            // input_filter
            if ($inputTypeProp['filter_types']) {
                $filterTypes = $this->_attributeHelper->getAttributeFilterTypes();
                $values = $form->getElement('input_filter')->getValues();
                foreach ($inputTypeProp['filter_types'] as $filterTypeCode) {
                    $values[$filterTypeCode] = $filterTypes[$filterTypeCode];
                }
                $form->getElement('input_filter')->setValues($values);
            }

            // input_validation getAttributeValidateFilters
            if ($inputTypeProp['validate_filters']) {
                $filterTypes = $this->_attributeHelper->getAttributeValidateFilters();
                $values = $form->getElement('input_validation')->getValues();
                foreach ($inputTypeProp['validate_filters'] as $filterTypeCode) {
                    $values[$filterTypeCode] = $filterTypes[$filterTypeCode];
                }
                $form->getElement('input_validation')->setValues($values);
            }
        }

        // apply scopes
        foreach ($this->_attributeHelper->getAttributeElementScopes() as $elementId => $scope) {
            $element = $form->getElement($elementId);
            if ($element) {
                $element->setScope($scope);
                if ($this->getAttributeObject()->getWebsite()->getId()) {
                    $element->setName('scope_' . $element->getName());
                }
            }
        }

        $this->getForm()->setDataObject($this->getAttributeObject());

        $htmlIdPrefix = $form->getHtmlIdPrefix();
        /** @var \Magento\Backend\Block\Widget\Form\Element\Dependence $dependenceBlock */
        $dependenceBlock = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Form\Element\Dependence::class
        );
        $this->setChild(
            'form_after',
            $dependenceBlock->addFieldMap($htmlIdPrefix . 'used_in_forms', 'used_in_forms')
                ->addFieldMap($htmlIdPrefix . 'is_visible', 'is_visible')
                ->addFieldDependence('used_in_forms', 'is_visible', '1')
        );

        return $this;
    }

    /**
     * Initialize form fileds values
     *
     * @return \Magento\Eav\Block\Adminhtml\Attribute\Edit\Main\AbstractMain
     */
    protected function _initFormValues()
    {
        $attribute = $this->getAttributeObject();
        if ($attribute->getId() && $attribute->getValidateRules()) {
            $this->getForm()->addValues($attribute->getValidateRules());
        }
        $result = parent::_initFormValues();

        // get data using methods to apply scope
        $formValues = $this->getAttributeObject()->getData();
        foreach (array_keys($formValues) as $idx) {
            $formValues[$idx] = $this->getAttributeObject()->getDataUsingMethod($idx);
        }
        $this->getForm()->addValues($formValues);

        return $result;
    }

    /**
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Properties');
    }

    /**
     * Return Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Properties');
    }

    /**
     * Can show tab in tabs
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Returns allowed input options
     *
     * @return array
     */
    public function getAttributeInputOptions()
    {
        /**
         * Restriction! RMA doesn't support next types of attributes - multiline, multiselect & date
         * @see MAGETWO-45043: Customer/Guest doesn't have ability create Return if required and visible on
         * frontend RMA attribute was created
         */
        $supportedInputOptions = [];
        $restrictedInputTypes = ['multiline', 'multiselect', 'date'];
        foreach ($this->_attributeHelper->getFrontendInputOptions() as $type) {
            if (!in_array($type['value'], $restrictedInputTypes)) {
                $supportedInputOptions[] = $type;
            }
        }
        return $supportedInputOptions;
    }
}
