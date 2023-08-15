<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model\Plugin;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\CustomerCustomAttributes\Model\CustomerAddressCustomAttributesProcessor;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Process custom guest attributes before saving billing address
 */
class ProcessGuestBillingAddressCustomAttributes
{
    /** @var CustomerAddressCustomAttributesProcessor */
    private $customerAddressCustomAttributesProcessor;

    /**
     * Constructor for billing custom attribute for guest user plugin
     *
     * @param CustomerAddressCustomAttributesProcessor $customerAddressCustomAttributesProcessor
     */
    public function __construct(
        CustomerAddressCustomAttributesProcessor $customerAddressCustomAttributesProcessor
    ) {
        $this->customerAddressCustomAttributesProcessor = $customerAddressCustomAttributesProcessor;
    }

    /**
     * Process billing custom attribute before save for guest
     *
     * @param GuestPaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        string $cartId,
        string $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        if ($billingAddress) {
            $this->customerAddressCustomAttributesProcessor->execute($billingAddress);
        }
    }
}
