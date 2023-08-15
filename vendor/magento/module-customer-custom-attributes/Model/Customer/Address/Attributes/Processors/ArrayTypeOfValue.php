<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\Processors;

use Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\ProcessorComponentInterface;
use Magento\Framework\Api\AttributeInterface;

/**
 * Transforms array type of customer address attribute value.
 */
class ArrayTypeOfValue implements ProcessorComponentInterface
{
    /**
     * @inheritDoc
     */
    public function process(AttributeInterface $attribute): void
    {
        if (!$this->isTransformationRequired($attribute)) {
            return;
        }

        $customAttributeValue = $attribute->getValue();
        $attribute->setValue($customAttributeValue['value']);
    }

    /**
     * Whether the attribute has signs that the transformation is required.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function isTransformationRequired(AttributeInterface $attribute): bool
    {
        $customAttributeValue = $attribute->getValue();

        return isset($customAttributeValue['value']) &&
            $customAttributeValue['value'] !== null;
    }
}
