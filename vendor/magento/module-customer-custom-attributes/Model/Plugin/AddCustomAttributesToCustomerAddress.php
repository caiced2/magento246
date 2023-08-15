<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Plugin;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Address;
use Magento\CustomerCustomAttributes\Helper\Data as Helper;
use Magento\Framework\Api\AttributeInterface;

/**
 * Plugin for converting customer address custom attributes
 */
class AddCustomAttributesToCustomerAddress
{
    /**
     * @var Helper
     */
    private $customerData;

    /**
     * @param Helper $customerData
     */
    public function __construct(
        Helper $customerData
    ) {
        $this->customerData = $customerData;
    }

    /**
     * Set custom attribute interface values for custom attributes
     *
     * @param Address $subject
     * @param AddressInterface $customerAddress
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeUpdateData(
        Address $subject,
        AddressInterface $customerAddress
    ) : array {
        $attributes = $this->customerData->getCustomerAddressUserDefinedAttributeCodes();
        $values = $customerAddress->getCustomAttributes();
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $values) && !($values[$attribute] instanceof AttributeInterface)) {
                $customerAddress->setCustomAttribute($attribute, $values[$attribute]);
            }
        }

        return [$customerAddress];
    }
}
