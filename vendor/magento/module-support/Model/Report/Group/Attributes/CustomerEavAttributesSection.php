<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Attributes;

use Magento\Customer\Api\CustomerMetadataInterface;

/**
 * Customer Eav Attributes section of Attributes report group
 */
class CustomerEavAttributesSection extends AbstractAttributesSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $attributeCollection = $this->getAttributesCollection(
            ['entity_type_id' => $this->getEntityTypeId(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER)]
        );
        return [
            (string)__('Customer Eav Attributes') => $this->generateSectionData(
                $attributeCollection,
                ['entity_type_code']
            )
        ];
    }
}
