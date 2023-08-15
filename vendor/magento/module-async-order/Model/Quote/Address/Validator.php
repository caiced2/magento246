<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AsyncOrder\Model\Quote\Address;

use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order\Address\Validator as OrderAddressValidator;

/**
 * Class for validating customer address
 */
class Validator extends OrderAddressValidator
{
    /**
     * @var array
     */
    protected $required = [
        'postcode' => 'Zip code',
        'lastname' => 'Last name',
        'street' => 'Street',
        'city' => 'City',
        'email' => 'Email',
        'country_id' => 'Country',
        'firstname' => 'First Name',
        'address_type' => 'Address Type',
    ];

    /**
     * Validate address for customer registration.
     *
     * @param Address $address
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validateForCustomerRegistration(Address $address):bool
    {
        if ($this->isEmpty($address->getFirstname())) {
            return false;
        }
        if ($this->isEmpty($address->getLastname())) {
            return false;
        }
        if ($this->isEmpty($address->getStreetLine(1))) {
            return false;
        }
        if ($this->isEmpty($address->getCity())) {
            return false;
        }
        if ($this->isTelephoneRequired() && $this->isEmpty($address->getTelephone())) {
            return false;
        }
        if ($this->isEmpty($address->getCountryId())) {
            return false;
        }
        if ($this->isZipRequired($address->getCountryId()) && $this->isEmpty($address->getPostcode())) {
            return false;
        }
        if (!in_array($address->getAddressType(), [Address::TYPE_BILLING, Address::TYPE_SHIPPING], true)) {
            return false;
        }
        return true;
    }
}
