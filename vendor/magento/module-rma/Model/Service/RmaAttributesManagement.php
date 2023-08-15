<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\Service;

use Magento\Eav\Model\Config;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\ObjectManager;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\Customer\Model\AttributeMetadataConverter;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;

/**
 * Rma attributes management service
 */
class RmaAttributesManagement implements RmaAttributesManagementInterface
{
    /**
     * List of item dto methods
     *
     * @var array
     */
    protected $dataObjectMethods = [];

    /**
     * Attribute metadata provider
     *
     * @var AttributeMetadataDataProvider
     */
    protected $metadataDataProvider;

    /**
     * Attribute metadata converter
     *
     * @var AttributeMetadataConverter
     */
    protected $metadataConverter;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * Constructor
     *
     * @param AttributeMetadataDataProvider $metadataDataProvider
     * @param AttributeMetadataConverter $metadataConverter
     * @param Config|null $eavConfig
     */
    public function __construct(
        AttributeMetadataDataProvider $metadataDataProvider,
        AttributeMetadataConverter $metadataConverter,
        ?Config $eavConfig = null
    ) {
        $this->metadataDataProvider = $metadataDataProvider;
        $this->metadataConverter = $metadataConverter;
        $this->eavConfig = $eavConfig ?? ObjectManager::getInstance()->get(Config::class);
    }

    /**
     * Retrieve all attributes filtered by form code
     *
     * @param string $formCode
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface[]
     */
    public function getAttributes($formCode)
    {
        $attributes = [];
        $attributesFormCollection = $this->metadataDataProvider->loadAttributesCollection(
            self::ENTITY_TYPE,
            $formCode
        );
        foreach ($attributesFormCollection as $attribute) {
            /** @var $attribute \Magento\Customer\Model\Attribute */
            $attributes[$attribute->getAttributeCode()] = $this->metadataConverter->createMetadataAttribute($attribute);
        }

        return $attributes;
    }

    /**
     * Retrieve attribute metadata
     *
     * @param string $attributeCode
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface
     * @throws NoSuchEntityException
     */
    public function getAttributeMetadata($attributeCode)
    {
        /** @var AbstractAttribute $attribute */
        $attribute = $this->metadataDataProvider->getAttribute(self::ENTITY_TYPE, $attributeCode);
        if ($attribute && $attribute->getIsVisible() == 1) {
            $attributeMetadata = $this->metadataConverter->createMetadataAttribute($attribute);
            return $attributeMetadata;
        } else {
            throw new NoSuchEntityException(
                __(
                    'No such entity with %1 = %2, %3 = %4',
                    'entityType',
                    self::ENTITY_TYPE,
                    'attributeCode',
                    $attributeCode
                )
            );
        }
    }

    /**
     * Get all attribute metadata
     *
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface[]
     */
    public function getAllAttributesMetadata()
    {
        $attributeSetId = (int) $this->eavConfig->getEntityType(self::ENTITY_TYPE)
            ->getDefaultAttributeSetId();
        /** @var AbstractAttribute[] $attribute */
        $attributeCodes = $this->metadataDataProvider->getAllAttributeCodes(
            self::ENTITY_TYPE,
            $attributeSetId
        );

        $attributesMetadata = [];
        foreach ($attributeCodes as $attributeCode) {
            try {
                $attributesMetadata[] = $this->getAttributeMetadata($attributeCode);
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }

        return $attributesMetadata;
    }

    /**
     *  Get custom attribute metadata for the given Data object's attribute set
     *
     * @param string|null $dataObjectClassName Data object class name
     * @return \Magento\Framework\Api\MetadataObjectInterface[]
     */
    public function getCustomAttributesMetadata($dataObjectClassName = self::DATA_OBJECT_CLASS_NAME)
    {
        $customAttributes = [];
        if (!$this->dataObjectMethods) {
            $this->dataObjectMethods = array_flip(get_class_methods($dataObjectClassName));
        }
        foreach ($this->getAllAttributesMetadata() as $attributeMetadata) {
            $attributeCode = $attributeMetadata->getAttributeCode();
            $camelCaseKey = SimpleDataObjectConverter::snakeCaseToUpperCamelCase($attributeCode);
            $isDataObjectMethod = isset($this->dataObjectMethods['get' . $camelCaseKey])
                || isset($this->dataObjectMethods['is' . $camelCaseKey]);

            /** Even though disable_auto_group_change is system attribute, it should be available to the clients */
            if (!$isDataObjectMethod
                && (!$attributeMetadata->isSystem() || $attributeCode == 'disable_auto_group_change')
            ) {
                $customAttributes[] = $attributeMetadata;
            }
        }

        return $customAttributes;
    }
}
