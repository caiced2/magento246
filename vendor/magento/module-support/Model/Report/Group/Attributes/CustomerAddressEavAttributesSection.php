<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Attributes;

use Magento\Customer\Api\AddressMetadataInterface;

/**
 * Customer Address Eav Attributes section of Attributes report group
 */
class CustomerAddressEavAttributesSection extends AbstractAttributesSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $attributeCollection = $this->getAttributesCollection(
            ['entity_type_id' => $this->getEntityTypeId(AddressMetadataInterface::ENTITY_TYPE_ADDRESS)]
        );
        return [
            (string)__('Customer Address Eav Attributes') => $this->generateSectionData(
                $attributeCollection,
                ['entity_type_code']
            )
        ];
    }
}
