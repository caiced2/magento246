<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Block\Adminhtml\Customer\Address\Attribute\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Element\Dependence as DependenceElement;
use Magento\Customer\Model\Config\Source\FilterConditionType;

/**
 * Customer Address Attribute General Tab Block
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class General extends \Magento\Eav\Block\Adminhtml\Attribute\Edit\Main\AbstractMain implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Customer custom attribute data
     *
     * @var \Magento\CustomerCustomAttributes\Helper\Data
     */
    protected $_customerData;

    /**
     * @var \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension
     */
    private $extensionValidator;

    /**
     * @var FilterConditionType
     */
    private $filterConditionType;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Eav\Helper\Data $eavData
     * @param \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory
     * @param \Magento\Eav\Model\Adminhtml\System\Config\Source\InputtypeFactory $inputTypeFactory
     * @param \Magento\Eav\Block\Adminhtml\Attribute\PropertyLocker $propertyLocker
     * @param \Magento\CustomerCustomAttributes\Helper\Data $customerData
     * @param array $data
     * @param \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension|null $extensionValidator
     * @param FilterConditionType|null $filterConditionType
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
        \Magento\CustomerCustomAttributes\Helper\Data $customerData,
        array $data = [],
        \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $extensionValidator = null,
        ?FilterConditionType $filterConditionType = null
    ) {
        $this->_customerData = $customerData;
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
        $this->filterConditionType = $filterConditionType
            ?: \Magento\Framework\App\ObjectManager::getInstance()
                ->get(FilterConditionType::class);
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
     * Adding customer address attribute form elements for edit form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();

        /** @var \Magento\Customer\Model\Attribute $attribute */
        $attribute = $this->getAttributeObject();
        $form = $this->getForm();
        $fieldset = $form->getElement('base_fieldset');

        $fieldset->removeField('frontend_class');
        $fieldset->removeField('is_unique');

        // update Input Types
        $values = $this->_customerData->getFrontendInputOptions();
        $element = $form->getElement('frontend_input');
        $element->setValues($values);
        $element->setLabel(__('Input Type'));
        $element->setRequired(true);

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
        $noteMessage ='Comma separated, alphanumeric characters. Forbidden file extensions: %1.';
        $fieldset->addField(
            'file_extensions',
            'text',
            [
                'name' => 'file_extensions',
                'label' => __('File Extensions'),
                'title' => __('File Extensions'),
                'note' => __($noteMessage, $forbiddenFileExtensions),
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
            'max_file_size'
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

        $fieldset->addField(
            'date_range_min',
            'date',
            [
                'name' => 'date_range_min',
                'label' => __('Minimal value'),
                'title' => __('Minimal value'),
                'date_format' => $this->_customerData->getDateFormat()
            ],
            'default_value_date'
        );

        $fieldset->addField(
            'date_range_max',
            'date',
            [
                'name' => 'date_range_max',
                'label' => __('Maximum value'),
                'title' => __('Maximum value'),
                'date_format' => $this->_customerData->getDateFormat()
            ],
            'date_range_min'
        );

        /** @var $source \Magento\Config\Model\Config\Source\Yesno */
        $source = $this->_yesnoFactory->create();
        $yesnoSource = $source->toOptionArray();

        $fieldset->addField(
            'is_used_in_grid',
            $attribute->getBackendType() == 'static' ? 'hidden' : 'select',
            [
                'name' => 'is_used_in_grid',
                'label' => __('Add to Column Options'),
                'title' => __('Add to Column Options'),
                'values' => $yesnoSource,
                'value' => $attribute->getData('is_used_in_grid') ?: 0,
                'note' => __('Select "Yes" to add this attribute to the list of column options in the customer grid.'),
            ]
        );

        $fieldset->addField(
            'is_visible_in_grid',
            'hidden',
            [
                'name' => 'is_visible_in_grid',
                'value' => $attribute->getData('is_visible_in_grid') ?: 0,
            ]
        );

        $fieldset->addField(
            'is_filterable_in_grid',
            'select',
            [
                'name' => 'is_filterable_in_grid',
                'label' => __('Use in Filter Options'),
                'title' => __('Use in Filter Options'),
                'values' => $yesnoSource,
                'value' => $attribute->getData('is_filterable_in_grid') ?: 0,
                'note' => __('Select "Yes" to add this attribute to the list of filter options in the customer grid.'),
            ]
        );

        $fieldset->addField(
            'is_searchable_in_grid',
            !$attribute->getId()
            || in_array($attribute->getFrontendInput(), ['text', 'textarea']) ? 'select' : 'hidden',
            [
                'name' => 'is_searchable_in_grid',
                'label' => __('Use in Search Options'),
                'title' => __('Use in Search Option'),
                'values' => $yesnoSource,
                'value' => $attribute->getData('is_searchable_in_grid') ?: 0,
                'note' => __('Select "Yes" to add this attribute to the list of search options in the customer grid.'),
            ]
        );

        $gridFilterConditionType = $fieldset->addField(
            'grid_filter_condition_type',
            !$attribute->getId()
            || in_array($attribute->getFrontendInput(), ['text', 'textarea']) ? 'select' : 'hidden',
            [
                'name' => 'grid_filter_condition_type',
                'label' => __('Grid Filter Condition Type'),
                'title' => __('Grid Filter Condition Type'),
                'values' => $this->filterConditionType->toOptionArray(),
                'value' => $attribute->getData('grid_filter_condition_type') ?: FilterConditionType::PARTIAL_MATCH,
                'note' => __('Select "Full Match" to equally compare the filter value with the attribute value.'),
            ],
            'is_filterable_in_grid'
        );
        if ($gridFilterConditionType->getType() !== 'hidden') {
            $dependenceBlock = $this->getLayout()->createBlock(DependenceElement::class);
            $dependenceBlock->addFieldMap('is_filterable_in_grid', 'is_filterable_in_grid_field')
                ->addFieldMap('grid_filter_condition_type', 'grid_filter_condition_type_field')
                ->addFieldDependence('grid_filter_condition_type_field', 'is_filterable_in_grid_field', '1');
            $this->setChild('form_after', $dependenceBlock);
        }

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
                'values' => $this->_customerData->getCustomerAddressAttributeFormOptions(),
                'value' => $attribute->getUsedInForms(),
                'can_be_empty' => true
            ]
        )->setSize(
            5
        );

        if ($attribute->getId()) {
            $elements = [];
            if ($attribute->getIsSystem()) {
                $elements = ['sort_order', 'is_visible', 'is_required', 'used_in_forms'];
            }
            if (!$attribute->getIsUserDefined() && !$attribute->getIsSystem()) {
                $elements = ['sort_order', 'used_in_forms'];
            }
            foreach ($elements as $elementId) {
                $form->getElement($elementId)->setDisabled(true);
            }

            $inputTypeProp = $this->_customerData->getAttributeInputTypes($attribute->getFrontendInput());

            // input_filter
            if ($inputTypeProp['filter_types']) {
                $filterTypes = $this->_customerData->getAttributeFilterTypes();
                $values = $form->getElement('input_filter')->getValues();
                foreach ($inputTypeProp['filter_types'] as $filterTypeCode) {
                    $values[$filterTypeCode] = $filterTypes[$filterTypeCode];
                }
                $form->getElement('input_filter')->setValues($values);
            }

            // input_validation getAttributeValidateFilters
            if ($inputTypeProp['validate_filters']) {
                $filterTypes = $this->_customerData->getAttributeValidateFilters();
                $values = $form->getElement('input_validation')->getValues();
                foreach ($inputTypeProp['validate_filters'] as $filterTypeCode) {
                    $values[$filterTypeCode] = $filterTypes[$filterTypeCode];
                }
                $form->getElement('input_validation')->setValues($values);
            }
        }

        // apply scopes
        foreach ($this->_customerData->getAttributeElementScopes() as $elementId => $scope) {
            $element = $form->getElement($elementId);
            if ($element->getDisabled()) {
                continue;
            }
            $element->setScope($scope);
            if ($this->getAttributeObject()->getWebsite()->getId()) {
                $element->setName('scope_' . $element->getName());
            }
        }

        $this->getForm()->setDataObject($this->getAttributeObject());

        $this->_eventManager->dispatch(
            'magento_customercustomattributes_address_attribute_edit_tab_general_prepare_form',
            ['form' => $form, 'attribute' => $attribute]
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
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }
}
