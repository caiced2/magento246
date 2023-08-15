<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Model\Quote\Address;

class CustomAttributeList implements \Magento\Quote\Model\Quote\Address\CustomAttributeListInterface
{
    /**
     * @var \Magento\Customer\Api\AddressMetadataInterface
     */
    protected $addressMetadata;

    /**
     * @var \Magento\Framework\Api\MetadataObjectInterface[]
     */
    protected $attributes = null;

    /**
     * @param \Magento\Customer\Api\AddressMetadataInterface $addressMetadata
     */
    public function __construct(\Magento\Customer\Api\AddressMetadataInterface $addressMetadata)
    {
        $this->addressMetadata = $addressMetadata;
    }

    /**
     * Retrieve list of quote address custom attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        if ($this->attributes === null) {
            $this->attributes = [];
            $customAttributesMetadata = $this->addressMetadata->getCustomAttributesMetadata(
                \Magento\Customer\Api\Data\AddressInterface::class
            );
            if (is_array($customAttributesMetadata)) {
                /** @var $attribute \Magento\Framework\Api\MetadataObjectInterface */
                foreach ($customAttributesMetadata as $attribute) {
                    $this->attributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
            $customAttributesMetadata = $this->addressMetadata->getCustomAttributesMetadata(
                \Magento\Customer\Api\Data\CustomerInterface::class
            );
            if (is_array($customAttributesMetadata)) {
                /** @var $attribute \Magento\Framework\Api\MetadataObjectInterface */
                foreach ($customAttributesMetadata as $attribute) {
                    $this->attributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }
        return $this->attributes;
    }
}
