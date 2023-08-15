<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\Processors;

use Magento\Framework\Api\AttributeInterface;
use Magento\Quote\Model\Quote\Address\CustomAttributeListInterface;
use Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\ProcessorComponentInterface;

/**
 * Transforms File type of customer address attribute.
 */
class FileTypeOfFrontendInput implements ProcessorComponentInterface
{
    /**
     * List of file input types
     */
    private const INPUT_TYPES = [
        'file',
        'image'
    ];

    /**
     * @var CustomAttributeListInterface
     */
    private $customAttributeList;

    /**
     * @param CustomAttributeListInterface $customAttributeList
     */
    public function __construct(CustomAttributeListInterface $customAttributeList)
    {
        $this->customAttributeList = $customAttributeList;
    }

    /**
     * @inheritDoc
     */
    public function process(AttributeInterface $attribute): void
    {
        if (!$this->isTransformationRequired($attribute)) {
            return;
        }

        $attributeValue = $attribute->getValue();
        $attribute->setValue($attributeValue['value'][0]['file']);
    }

    /**
     * Whether the attribute has signs that the transformation is required.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function isTransformationRequired(AttributeInterface $attribute): bool
    {
        $attributesMetaData = $this->customAttributeList->getAttributes();

        if (!isset($attributesMetaData[$attribute->getAttributeCode()])) {
            return false;
        }

        $attributeMetaData = $attributesMetaData[$attribute->getAttributeCode()];

        if (!in_array($attributeMetaData->getFrontendInput(), self::INPUT_TYPES, true)) {
            return false;
        }

        $attributeValue = $attribute->getValue();

        return is_array($attributeValue) &&
            isset($attributeValue['value'][0]['file']);
    }
}
