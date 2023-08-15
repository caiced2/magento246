<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\CustomerCustomAttributes\Model\Customer\Address\Attributes\ProcessorComponentInterface;
use Magento\Quote\Model\Quote\Address\CustomAttributeListInterface;

/**
 * Helper class for processing shipping or billing custom attributes
 */
class CustomerAddressCustomAttributesProcessor
{
    /**
     * @var int[]
     */
    private $processedAttributes = [];

    /**
     * @var ProcessorComponentInterface[]
     */
    private $processors;

    /**
     * @param ProcessorComponentInterface[] $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    /**
     * Process customer custom attribute before save shipping or billing address
     *
     * @param AddressInterface $addressInformation
     * @return void
     */
    public function execute(AddressInterface $addressInformation): void
    {
        $customerCustomAttributes = $addressInformation->getCustomAttributes();
        if ($customerCustomAttributes) {
            foreach ($customerCustomAttributes as $customAttribute) {
                $this->processAttribute($customAttribute);
            }
        }
    }

    /**
     * Transform attribute to Model data format.
     *
     * @param AttributeInterface $attribute
     * @return void
     */
    private function processAttribute(AttributeInterface $attribute): void
    {
        // Make sure the same attribute won't be processed repeatedly
        if ($this->hasBeenProcessed($attribute)) {
            return;
        }

        // Make attribute transformations based on various signs
        foreach ($this->processors as $processor) {
            $processor->process($attribute);
        }

        $this->registerProcessedAttribute($attribute);
    }

    /**
     * Whether attribute has been already processed.
     *
     * @param AttributeInterface $attribute
     * @return bool
     */
    private function hasBeenProcessed(AttributeInterface $attribute): bool
    {
        $objectId = spl_object_id($attribute);

        return in_array($objectId, $this->processedAttributes);
    }

    /**
     * Register processed attribute.
     *
     * @param AttributeInterface $attribute
     */
    private function registerProcessedAttribute(AttributeInterface $attribute): void
    {
        $this->processedAttributes[] = spl_object_id($attribute);
    }
}
