<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\AddressValidator;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\QuickCheckout\Api\Data\AddressInterface;
use Magento\QuickCheckout\Model\AddressValidatorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Directory\Model\AllowedCountries;

/**
 * Validate basic address fields
 */
class Fields implements AddressValidatorInterface
{
    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var AllowedCountries
     */
    private $allowedCountries;

    /**
     * @param EavConfig $eavConfig
     * @param AllowedCountries $allowedCountries
     */
    public function __construct(
        EavConfig $eavConfig,
        AllowedCountries $allowedCountries
    ) {
        $this->eavConfig = $eavConfig;
        $this->allowedCountries = $allowedCountries;
    }

    /**
     * Validate the address
     *
     * @param AddressInterface $address
     * @return bool
     */
    public function validate(AddressInterface $address): bool
    {
        if ($this->isPhoneNumberRequired() && $this->isEmpty($address->getTelephone())) {
            return false;
        }
        if ($this->isCompanyRequired() && $this->isEmpty($address->getCompany())) {
            return false;
        }
        $countryId = $address->getCountryId();
        if ($this->isEmpty($countryId) || !$this->isCountryAllowedForWebsite($countryId)) {
            return false;
        }
        return true;
    }

    /**
     * Check if value is empty
     *
     * @param mixed $value
     * @return bool
     */
    private function isEmpty($value): bool
    {
        return empty($value);
    }

    /**
     * Check is address allowed for store
     *
     * @param string $countryId
     * @return bool
     */
    private function isCountryAllowedForWebsite(string $countryId): bool
    {
        return in_array($countryId, $this->allowedCountries->getAllowedCountries(ScopeInterface::SCOPE_STORE));
    }

    /**
     * Check whether phone number is required for address
     *
     * @return bool
     */
    private function isPhoneNumberRequired(): bool
    {
        try {
            return (bool) $this->eavConfig->getAttribute('customer_address', 'telephone')
                ->getIsRequired();
        // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
        } catch (LocalizedException $e) {
        }
        return false;
    }

    /**
     * Check whether company is required for address
     *
     * @return bool
     */
    private function isCompanyRequired(): bool
    {
        try {
            return (bool) $this->eavConfig->getAttribute('customer_address', 'company')
                ->getIsRequired();
        // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
        } catch (LocalizedException $e) {
        }
        return false;
    }
}
