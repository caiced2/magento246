<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * RMA Helper
 */
namespace Magento\Rma\Helper;

class Eav extends \Magento\Eav\Helper\Data
{
    /**
     * Complicated array of select-typed attribute values for all stores
     *
     * @var array
     */
    protected $_attributeOptionValues = [];

    /**
     * Core store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Eav entity attribute option collection
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Application resource
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Eav\Model\Entity\Attribute\Config $attributeConfig
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Model\Entity\Attribute\Config $attributeConfig,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $collectionFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_storeManager = $storeManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_resource = $resource;
        parent::__construct($context, $attributeConfig, $eavConfig);
    }

    /**
     * Default attribute entity type code
     *
     * @return string
     */
    protected function _getEntityTypeCode()
    {
        return 'rma_item';
    }

    /**
     * Return data array of RMA item attribute Input Types
     *
     * @param string|null $inputType
     * @return array
     */
    public function getAttributeInputTypes($inputType = null)
    {
        $inputTypes = [
            'text' => [
                'label' => __('Text Field'),
                'manage_options' => false,
                'validate_types' => ['min_text_length', 'max_text_length'],
                'validate_filters' => ['alphanumeric', 'numeric', 'alpha', 'url', 'email'],
                'filter_types' => ['striptags', 'escapehtml'],
                'backend_type' => 'varchar',
                'default_value' => 'text',
            ],
            'textarea' => [
                'label' => __('Text Area'),
                'manage_options' => false,
                'validate_types' => ['min_text_length', 'max_text_length'],
                'validate_filters' => [],
                'filter_types' => ['striptags', 'escapehtml'],
                'backend_type' => 'text',
                'default_value' => 'textarea',
            ],
            'select' => [
                'label' => __('Dropdown'),
                'manage_options' => true,
                'option_default' => 'radio',
                'validate_types' => [],
                'validate_filters' => [],
                'filter_types' => [],
                'source_model' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                'backend_type' => 'int',
                'default_value' => false,
            ],
            'image' => [
                'label' => __('Image File'),
                'manage_options' => false,
                'validate_types' => ['max_file_size', 'max_image_width', 'max_image_heght'],
                'validate_filters' => [],
                'filter_types' => [],
                'backend_type' => 'varchar',
                'default_value' => false,
            ],
        ];

        if ($inputType === null) {
            return $inputTypes;
        } elseif (isset($inputTypes[$inputType])) {
            return $inputTypes[$inputType];
        }
        return [];
    }

    /**
     * Get array of select-typed attribute values depending by store
     *
     * Uses internal protected method, which must use data from protected variable
     *
     * @param null|int|\Magento\Store\Model\Store $storeId
     * @param bool $useDefaultValue
     * @return array
     */
    public function getAttributeOptionStringValues($storeId = null, $useDefaultValue = true)
    {
        $values = $this->_getAttributeOptionValues($storeId, $useDefaultValue);
        $return = [];
        foreach ($values as $temValue) {
            foreach ($temValue as $value) {
                $return[$value['option_id']] = $value['value'];
            }
        }
        return $return;
    }

    /**
     * Get array of key=>value pair for passed attribute code depending by store
     *
     * Uses internal protected method, which must use data from protected variable
     *
     * @param string $attributeCode
     * @param null|int|\Magento\Store\Model\Store $storeId
     * @param bool $useDefaultValue
     * @return array
     */
    public function getAttributeOptionValues($attributeCode, $storeId = null, $useDefaultValue = true)
    {
        $values = $this->_getAttributeOptionValues($storeId, $useDefaultValue);
        $return = [];
        if (isset($values[$attributeCode])) {
            foreach ($values[$attributeCode] as $key => $value) {
                $return[$key] = $value['value'];
            }
        }
        return $return;
    }

    /**
     * Get complicated array of select-typed attribute values depending by store
     *
     * @param null|int|\Magento\Store\Model\Store $storeId
     * @param bool $useDefaultValue
     * @return array
     */
    protected function _getAttributeOptionValues($storeId = null, $useDefaultValue = true)
    {
        if ($storeId === null) {
            $storeId = $this->_storeManager->getStore()->getId();
        } elseif ($storeId instanceof \Magento\Store\Model\Store) {
            $storeId = $storeId->getId();
        }

        if (!isset($this->_attributeOptionValues[$storeId])) {
            $optionCollection = $this->_collectionFactory->create()->setStoreFilter($storeId, $useDefaultValue);
            $optionCollection->getSelect()->join(
                ['ea' => $this->_resource->getTableName('eav_attribute')],
                'main_table.attribute_id = ea.attribute_id',
                ['attribute_code' => 'ea.attribute_code']
            )->join(
                ['eat' => $this->_resource->getTableName('eav_entity_type')],
                'ea.entity_type_id = eat.entity_type_id',
                ['']
            )->where(
                'eat.entity_type_code = ?',
                $this->_getEntityTypeCode()
            );
            $value = [];
            foreach ($optionCollection as $option) {
                $value[$option->getAttributeCode()][$option->getOptionId()] = $option->getData();
            }
            $this->_attributeOptionValues[$storeId] = $value;
        }
        return $this->_attributeOptionValues[$storeId];
    }

    /**
     * Retrieve additional style classes for text-based RMA attributes (represented by text input or textarea)
     *
     * @param \Magento\Framework\DataObject $attribute
     * @return string[]
     */
    public function getAdditionalTextElementClasses(\Magento\Framework\DataObject $attribute)
    {
        $additionalClasses = [];

        $validateRules = $attribute->getValidateRules();
        if (!empty($validateRules['min_text_length'])) {
            $additionalClasses[] = 'validate-length';
            $additionalClasses[] = 'minimum-length-' . $validateRules['min_text_length'];
        }
        if (!empty($validateRules['max_text_length'])) {
            if (!in_array('validate-length', $additionalClasses)) {
                $additionalClasses[] = 'validate-length';
            }
            $additionalClasses[] = 'maximum-length-' . $validateRules['max_text_length'];
        }

        return $additionalClasses;
    }

    /**
     * Check is required attribute
     *
     * @param string $attributeCode
     * @return bool
     */
    public function isRequiredAttribute(string $attributeCode): bool
    {
        return $this->_eavConfig->getAttribute($this->_getEntityTypeCode(), $attributeCode)->getIsRequired();
    }
}
