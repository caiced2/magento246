<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Model\Plugin;

class ConvertQuoteAddressToCustomerAddress
{
    /**
     * @var \Magento\CustomerCustomAttributes\Helper\Data
     */
    private $customerData;

    /**
     * @param \Magento\CustomerCustomAttributes\Helper\Data $customerData
     */
    public function __construct(
        \Magento\CustomerCustomAttributes\Helper\Data $customerData
    ) {
        $this->customerData = $customerData;
    }

    /**
     * @param \Magento\Quote\Api\Data\AddressInterface $quoteAddress
     * @param \Magento\Customer\Api\Data\AddressInterface $customerAddress
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function afterExportCustomerAddress(
        \Magento\Quote\Api\Data\AddressInterface $quoteAddress,
        \Magento\Customer\Api\Data\AddressInterface $customerAddress
    ) {
        $attributes = $this->customerData->getCustomerAddressUserDefinedAttributeCodes();
        foreach ($attributes as $attribute) {
            $customerAddress->setCustomAttribute($attribute, $quoteAddress->getData($attribute));
        }
        return $customerAddress;
    }
}
