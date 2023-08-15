<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Attributes;

use Magento\Catalog\Api\Data\ProductAttributeInterface;

/**
 * Product Eav Attributes section of Attributes report group
 */
class ProductEavAttributesSection extends AbstractAttributesSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $attributeCollection = $this->getAttributesCollection(
            ['entity_type_id' => $this->getEntityTypeId(ProductAttributeInterface::ENTITY_TYPE_CODE)]
        );
        return [
            (string)__('Product Eav Attributes') => $this->generateSectionData(
                $attributeCollection,
                ['entity_type_code']
            )
        ];
    }
}
