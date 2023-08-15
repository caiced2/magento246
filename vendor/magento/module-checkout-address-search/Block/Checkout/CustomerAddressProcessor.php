<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CheckoutAddressSearch\Block\Checkout;

use Magento\CheckoutAddressSearch\Model\Config;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Address\CustomerAddressDataFormatter;
use Magento\Customer\Model\Address\CustomAttributesProcessor;

class CustomerAddressProcessor
{
    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * Customer data with limited number of addresses
     *
     * @var array
     */
    private $customerAddresses = [];

    /**
     * @var CustomerAddressDataFormatter
     */
    private $customerAddressDataFormatter;

    /**
     * @var CustomAttributesProcessor
     */
    private $customAttributesProcessor;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param CustomerRepository $customerRepository
     * @param CustomerAddressDataFormatter $customerAddressDataFormatter
     * @param CustomAttributesProcessor $customAttributesProcessor
     * @param Config $config
     */
    public function __construct(
        CustomerRepository $customerRepository,
        CustomerAddressDataFormatter $customerAddressDataFormatter,
        CustomAttributesProcessor $customAttributesProcessor,
        Config $config
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerAddressDataFormatter = $customerAddressDataFormatter;
        $this->customAttributesProcessor = $customAttributesProcessor;
        $this->config = $config;
    }

    /**
     * Get formatted options by user context and address search config.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFormattedOptions(\Magento\Quote\Model\Quote $quote): array
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $this->customerRepository->getById($quote->getCustomerId());
        $customerAddressesData = $this->getCustomerAddressData($customer, $quote);

        $addresses = [];
        foreach ($customerAddressesData as $address) {
            if (isset($address['custom_attributes'])) {
                $address['custom_attributes'] = $this->customAttributesProcessor->filterNotVisibleAttributes(
                    $address['custom_attributes']
                );
            }
            $address['value'] = $address['id'];
            $addresses[] = $address;
        }

        return $addresses;
    }

    /**
     * Returns customer addresses data.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCustomerAddressData(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        \Magento\Quote\Model\Quote $quote
    ): array {
        if (!empty($this->customerAddresses)) {
            return $this->customerAddresses;
        }

        $customerOriginAddresses = $customer->getAddresses();
        if (!$customerOriginAddresses) {
            return [];
        }

        $pageSize = $this->config->getPageSize();
        $customerOriginAddresses = \array_slice(
            $customerOriginAddresses,
            count($customerOriginAddresses) - $pageSize,
            $pageSize,
            true
        );

        $customerAddresses = [];
        foreach ($customerOriginAddresses as $address) {
            $customerAddresses[$address->getId()] = $this->customerAddressDataFormatter->prepareAddress($address);
        }

        // add default address if there is such to the limited collection of addresses
        $defaultShippingId = $customer->getDefaultShipping();
        if (!empty($defaultShippingId) && !isset($customerAddresses[$defaultShippingId])) {
            $filteredDefaultAddress = array_filter(
                $customer->getAddresses(),
                function ($address) use ($defaultShippingId) {
                    return $address->getId() === $defaultShippingId;
                }
            );
            $defaultShippingAddress = $this->customerAddressDataFormatter
                ->prepareAddress(current($filteredDefaultAddress));
            $customerAddresses = [$defaultShippingId => $defaultShippingAddress] + $customerAddresses;
            array_pop($customerAddresses);
        }

        $this->customerAddresses = $this->prepareSelectedAddresses(
            $customer,
            $quote,
            $customerAddresses
        );

        return $this->customerAddresses;
    }

    /**
     * Prepare list of addressed that was selected by customer on checkout page.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $prepareAddressList
     * @return array
     */
    public function prepareSelectedAddresses(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        \Magento\Quote\Model\Quote $quote,
        array $prepareAddressList
    ): array {
        /** @var AddressInterface $billingAddress */
        $billingAddress = $quote->getBillingAddress();
        $billingAddressId = $billingAddress->getOrigData('customer_address_id');
        $prepareAddressList = $this->prepareSelectedAddress($customer, $prepareAddressList, $billingAddressId);

        $shippingAddressId = null;
        $shippingAssignments = $quote->getExtensionAttributes()->getShippingAssignments();
        if (isset($shippingAssignments[0])) {
            $shipping = current($shippingAssignments)->getData('shipping');
            /** @var AddressInterface $shippingAddress */
            $shippingAddress = $shipping->getAddress();
            $shippingAddressId = $shippingAddress->getOrigData('customer_address_id');
        }

        $prepareAddressList = $this->prepareSelectedAddress($customer, $prepareAddressList, $shippingAddressId);

        return $prepareAddressList;
    }

    /**
     * Prepared address by for given customer with given address id.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param array $addressList
     * @param int|null $addressId
     * @return array
     */
    private function prepareSelectedAddress(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        array $addressList,
        $addressId = null
    ): array {
        if (null !== $addressId && !isset($addressList[$addressId])) {
            $filteredAddresses = array_filter(
                $customer->getAddresses(),
                function ($address) use ($addressId) {
                    return $address->getId() === $addressId;
                }
            );
            if (!empty($filteredAddresses)) {
                $selectedAddress = $this->customerAddressDataFormatter
                    ->prepareAddress(current($filteredAddresses));
                if (isset($selectedAddress['id'])) {
                    $addressList[$selectedAddress['id']] = $selectedAddress;
                }
            }
        }

        return $addressList;
    }
}
