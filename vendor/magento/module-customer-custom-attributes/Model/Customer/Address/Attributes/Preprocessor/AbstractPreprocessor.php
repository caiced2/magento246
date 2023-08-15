<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\Preprocessor;

use Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\AttributeRepository;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\CustomAttribute\PreprocessorInterface;

/**
 * Abstract customer address attribute preprocessor
 */
abstract class AbstractPreprocessor implements PreprocessorInterface
{
    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var array
     */
    private $attributesByType = [];

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param AttributeRepository $attributeRepository
     * @param EavConfig $eavConfig
     */
    public function __construct(
        AttributeCollectionFactory $attributeCollectionFactory,
        AttributeRepository $attributeRepository,
        EavConfig $eavConfig
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->attributeRepository = $attributeRepository;
        $this->eavConfig = $eavConfig;
    }

    /**
     * Check if requested attribute has valid frontend input type
     *
     * @param string $attributeCode
     * @param string $inputType
     * @return bool
     * @throws LocalizedException
     */
    protected function checkAttributeFrontendInput(string $attributeCode, string $inputType): bool
    {
        $entityType = $this->eavConfig->getEntityType('customer_address');
        try {
            $attribute = $this->attributeRepository->get($entityType, $attributeCode);
            return $attribute->getFrontendInput() == $inputType;
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * Get list of custom attributes by its frontend input type
     *
     * @param string $type
     * @return array
     */
    protected function getAttributesListByInputType(string $type): array
    {
        if (!array_key_exists($type, $this->attributesByType)) {
            $collection = $this->attributeCollectionFactory->create();
            $collection->addSystemHiddenFilter()->addExcludeHiddenFrontendFilter();
            $collection->addFieldToFilter(AttributeInterface::FRONTEND_INPUT, $type);
            $collection->removeAllFieldsFromSelect()->addFieldToSelect(AttributeInterface::ATTRIBUTE_CODE);
            $this->attributesByType[$type] = $collection->getColumnValues(AttributeInterface::ATTRIBUTE_CODE);
        }
        return $this->attributesByType[$type];
    }
}
