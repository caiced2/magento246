<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Plugin;

use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\CustomerCustomAttributes\Model\CustomerAddressCustomAttributesProcessor;

/**
 * Process custom customer attributes before saving shipping and billing address
 */
class ProcessCustomerShippingAddressCustomAttributes
{
    /** @var CustomerAddressCustomAttributesProcessor */
    private $customerAddressCustomAttributesProcessor;

    /**
     * Constructor for shipping and billing custom attribute for registered user plugin
     *
     * @param CustomerAddressCustomAttributesProcessor $customerAddressCustomAttributesProcessor
     */
    public function __construct(
        CustomerAddressCustomAttributesProcessor $customerAddressCustomAttributesProcessor
    ) {
        $this->customerAddressCustomAttributesProcessor = $customerAddressCustomAttributesProcessor;
    }

    /**
     * Process shipping and billing custom attribute before save for registered customer
     *
     * @param ShippingInformationManagementInterface $subject
     * @param string $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagementInterface $subject,
        string $cartId,
        ShippingInformationInterface $addressInformation
    ): void {
        $shippingAddress = $addressInformation->getShippingAddress();
        if ($shippingAddress) {
            $this->customerAddressCustomAttributesProcessor->execute($shippingAddress);
        }

        $billingAddress = $addressInformation->getBillingAddress();
        if ($billingAddress) {
            $this->customerAddressCustomAttributesProcessor->execute($billingAddress);
        }
    }
}
