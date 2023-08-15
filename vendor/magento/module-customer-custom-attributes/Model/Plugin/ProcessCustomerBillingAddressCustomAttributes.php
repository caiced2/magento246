<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Plugin;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\CustomerCustomAttributes\Model\CustomerAddressCustomAttributesProcessor;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Process custom customer attributes before saving billing address
 */
class ProcessCustomerBillingAddressCustomAttributes
{
    /** @var CustomerAddressCustomAttributesProcessor */
    private $customerAddressCustomAttributesProcessor;

    /**
     * Constructor for billing custom attribute for registered user plugin
     *
     * @param CustomerAddressCustomAttributesProcessor $customerAddressCustomAttributesProcessor
     */
    public function __construct(
        CustomerAddressCustomAttributesProcessor $customerAddressCustomAttributesProcessor
    ) {
        $this->customerAddressCustomAttributesProcessor = $customerAddressCustomAttributesProcessor;
    }

    /**
     * Process billing custom attribute before save for registered customer
     *
     * @param PaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSavePaymentInformation(
        PaymentInformationManagementInterface $subject,
        string $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        if ($billingAddress) {
            $this->customerAddressCustomAttributesProcessor->execute($billingAddress);
        }
    }
}
