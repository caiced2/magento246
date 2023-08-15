<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Attributes;

use Magento\Support\Model\Report\Group\AbstractSection;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Entity\TypeFactory as EntityTypeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Type as EntityTypeResource;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Data\Collection;

/**
 * Abstract section for Attributes report group sections
 */
abstract class AbstractAttributesSection extends AbstractSection
{
    /**
     * @var \Magento\Eav\Model\Entity\TypeFactory
     */
    protected $entityTypeFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Type
     */
    protected $entityTypeResource;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @var \Magento\Support\Model\Report\Group\Attributes\DataFormatter
     */
    protected $dataFormatter;

    /**
     * @var array
     */
    protected $fields = [
        'attribute_id' => 'ID',
        'attribute_code' => 'Code',
        'is_user_defined' => 'User Defined',
        'entity_type_code' => 'Entity Type Code',
        'source_model' => 'Source Model',
        'backend_model' => 'Backend Model',
        'frontend_model' => 'Frontend Model'
    ];

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Eav\Model\Entity\TypeFactory $entityTypeFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Type $entityTypeResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Magento\Support\Model\Report\Group\Attributes\DataFormatter $dataFormatter
     * @param array $data
     */
    public function __construct(
        LoggerInterface $logger,
        EntityTypeFactory $entityTypeFactory,
        EntityTypeResource $entityTypeResource,
        AttributeCollectionFactory $attributeCollectionFactory,
        DataFormatter $dataFormatter,
        array $data = []
    ) {
        $this->entityTypeFactory = $entityTypeFactory;
        $this->entityTypeResource = $entityTypeResource;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->dataFormatter = $dataFormatter;
        parent::__construct($logger, $data);
    }

    /**
     * Get Eav entity type id by code
     *
     * @param string $code
     * @return string
     */
    protected function getEntityTypeId($code)
    {
        /** @var \Magento\Eav\Model\Entity\Type $entityType */
        $entityType = $this->entityTypeFactory->create();
        $this->entityTypeResource->loadByCode($entityType, $code);
        return $entityType->getId();
    }

    /**
     * Get Eav attributes collection model populated with data
     *
     * @param array $filters
     * @return \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    protected function getAttributesCollection(array $filters = [])
    {
        /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $attributeCollection */
        $attributeCollection = $this->attributeCollectionFactory->create();
        foreach ($filters as $field => $condition) {
            $attributeCollection->addFieldToFilter($field, $condition);
        }
        $attributeCollection->setOrder('attribute_code', Collection::SORT_ORDER_ASC);
        $attributeCollection->load();
        return $attributeCollection;
    }

    /**
     * Generate section data
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $attributeCollection
     * @param array $excludedFields
     * @return array
     */
    protected function generateSectionData(
        AttributeCollection $attributeCollection,
        array $excludedFields = []
    ) {
        return [
            'headers' => $this->generateHeaders($excludedFields),
            'data' => $this->extractAttributeCollectionData($attributeCollection, $excludedFields)
        ];
    }

    /**
     * Extract data from collection of Eav attributes
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $attributeCollection
     * @param array $excludedFields
     * @return array
     */
    protected function extractAttributeCollectionData(
        AttributeCollection $attributeCollection,
        array $excludedFields = []
    ) {
        $data = [];
        /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            $data[] = $this->extractAttributeData($attribute, $excludedFields);
        }
        return $data;
    }

    /**
     * Extract data from Eav attribute
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param array $excludedFields
     * @return array
     */
    protected function extractAttributeData(Attribute $attribute, array $excludedFields)
    {
        $data = [];
        foreach (array_keys($this->fields) as $field) {
            if (!in_array($field, $excludedFields)) {
                $data[] = $this->extractAttributeValue($attribute, $field);
            }
        }
        return $data;
    }

    /**
     * Extract value from Eav attribute
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param string $field
     * @return string
     */
    protected function extractAttributeValue(Attribute $attribute, $field)
    {
        switch ($field) {
            case 'attribute_code':
                return $this->prepareCodeValue(
                    $attribute->getAttributeCode(),
                    $attribute->getFrontendInput(),
                    $attribute->getBackendType()
                );
                break;
            case 'is_user_defined':
                return $attribute->getIsUserDefined() ? __('Yes') : __('No');
                break;
            case 'entity_type_code':
                return $attribute->getEntityType()->getEntityTypeCode();
                break;
            case 'source_model':
            case 'backend_model':
            case 'frontend_model':
                return $this->dataFormatter->prepareModelValue($attribute->getData($field));
                break;
            default:
                return $attribute->getData($field);
                break;
        }
    }

    /**
     * Generate headers for group section
     *
     * @param array $excludedFields
     * @return array
     */
    protected function generateHeaders(array $excludedFields)
    {
        $headers = [];
        foreach ($this->fields as $field => $header) {
            if (!in_array($field, $excludedFields)) {
                $headers[] = __($header);
            }
        }
        return $headers;
    }

    /**
     * Prepare value for code column
     *
     * @param string $code
     * @param string $frontendInput
     * @param string $backendType
     * @return string
     */
    protected function prepareCodeValue($code, $frontendInput, $backendType)
    {
        return $code . "\n" . '{frontend: ' . $frontendInput . ', backend: ' . $backendType . '}';
    }
}
