<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Attributes;

use Magento\Catalog\Api\Data\CategoryAttributeInterface;

/**
 * Category Eav Attributes section of Attributes report group
 */
class CategoryEavAttributesSection extends AbstractAttributesSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $attributeCollection = $this->getAttributesCollection(
            ['entity_type_id' => $this->getEntityTypeId(CategoryAttributeInterface::ENTITY_TYPE_CODE)]
        );
        return [
            (string)__('Category Eav Attributes') => $this->generateSectionData(
                $attributeCollection,
                ['entity_type_code']
            )
        ];
    }
}
