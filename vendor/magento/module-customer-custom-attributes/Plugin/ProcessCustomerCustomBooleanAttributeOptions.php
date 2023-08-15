<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Plugin;

use Magento\Customer\Model\Attribute;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Ui\Component\Form\AttributeMapper;

/**
 * Class ProcessCustomerCustomBooleanAttributeOptions
 *
 * Process customer custom boolean attribute options and change it
 * to boolean values
 */
class ProcessCustomerCustomBooleanAttributeOptions
{
    /**
     * After map custom boolean attributes plugin.
     *
     * @param AttributeMapper $attributeMapper
     * @param array $meta
     * @param AttributeInterface $attribute
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterMap(
        AttributeMapper $attributeMapper,
        array $meta,
        AttributeInterface $attribute
    ): array {
        if ($attribute instanceof Attribute &&
            $this->isCustomAttributeBoolean($attribute) &&
            !empty($meta['options'])) {
            foreach ($meta['options'] as $key => $option) {
                $meta['options'][$key]['value'] = (bool) $option['value'];
            }
        }
        return $meta;
    }

    /**
     * Check if custom attribute is boolean
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function isCustomAttributeBoolean(AttributeInterface $attribute): bool
    {
        $isBoolean = (int) $attribute->getIsUserDefined() &&
            $attribute->getFrontendInput() == 'boolean';
        return (bool) $isBoolean;
    }
}
