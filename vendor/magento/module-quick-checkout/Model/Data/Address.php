<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Data;

use Magento\Framework\DataObject;
use Magento\QuickCheckout\Api\Data\AddressInterface;

/**
 * Account address data object
 */
class Address extends DataObject implements AddressInterface
{
    /**
     * Get ID
     *
     * @return string
     */
    public function getId(): string
    {
        return ($this->_getData(self::ID) ?? '');
    }

    /**
     * Set ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): AddressInterface
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get region
     *
     * @return string|null
     */
    public function getRegion(): ?string
    {
        return $this->_getData(self::REGION);
    }

    /**
     * Set region
     *
     * @param string|null $region
     * @return $this
     */
    public function setRegion(?string $region): AddressInterface
    {
        return $this->setData(self::REGION, $region);
    }

    /**
     * Get region code
     *
     * @return string|null
     */
    public function getRegionCode(): ?string
    {
        return $this->_getData(self::REGION_CODE);
    }

    /**
     * Set region code
     *
     * @param string|null $regionCode
     * @return $this
     */
    public function setRegionCode(?string $regionCode): AddressInterface
    {
        return $this->setData(self::REGION_CODE, $regionCode);
    }

    /**
     * Get region id
     *
     * @return string|null
     */
    public function getRegionId(): ?int
    {
        return $this->_getData(self::REGION_ID);
    }

    /**
     * Set region ID
     *
     * @param int|null $regionId
     * @return $this
     */
    public function setRegionId(?int $regionId): AddressInterface
    {
        return $this->setData(self::REGION_ID, $regionId);
    }

    /**
     * Two-letter country code in ISO_3166-2 format
     *
     * @return string
     */
    public function getCountryId(): string
    {
        return ($this->_getData(self::COUNTRY_ID) ?? '');
    }

    /**
     * Set country id
     *
     * @param string $countryId
     * @return $this
     */
    public function setCountryId(string $countryId): AddressInterface
    {
        return $this->setData(self::COUNTRY_ID, $countryId);
    }

    /**
     * Get street
     *
     * @return string[]|array
     */
    public function getStreet(): array
    {
        return ($this->_getData(self::STREET) ?? []);
    }

    /**
     * Set street
     *
     * @param string[] $street
     * @return $this
     */
    public function setStreet(array $street): AddressInterface
    {
        return $this->setData(self::STREET, $street);
    }

    /**
     * Get telephone number
     *
     * @return string
     */
    public function getTelephone(): string
    {
        return ($this->_getData(self::TELEPHONE) ?? '');
    }

    /**
     * Set telephone number
     *
     * @param string $telephone
     * @return $this
     */
    public function setTelephone(string $telephone): AddressInterface
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }

    /**
     * Get postcode
     *
     * @return string
     */
    public function getPostcode(): string
    {
        return ($this->_getData(self::POSTCODE) ?? '');
    }

    /**
     * Set postcode
     *
     * @param string $postcode
     * @return $this
     */
    public function setPostcode(string $postcode): AddressInterface
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    /**
     * Get city name
     *
     * @return string|null
     */
    public function getCity(): string
    {
        return ($this->_getData(self::CITY) ?? '');
    }

    /**
     * Set city name
     *
     * @param string $city
     * @return $this
     */
    public function setCity(string $city): AddressInterface
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstname(): string
    {
        return ($this->_getData(self::FIRSTNAME) ?? '');
    }

    /**
     * Set first name
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstname(string $firstName): AddressInterface
    {
        return $this->setData(self::FIRSTNAME, $firstName);
    }

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastname(): string
    {
        return ($this->_getData(self::LASTNAME) ?? '');
    }

    /**
     * Set last name
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastname(string $lastName): AddressInterface
    {
        return $this->setData(self::LASTNAME, $lastName);
    }

    /**
     * Get company
     *
     * @return string
     */
    public function getCompany(): string
    {
        return ($this->_getData(self::COMPANY) ?? '');
    }

    /**
     * Set company
     *
     * @param string $company
     * @return $this
     */
    public function setCompany(string $company): AddressInterface
    {
        return $this->setData(self::COMPANY, $company);
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return ($this->_getData(self::EMAIL) ?? '');
    }

    /**
     * Set email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): AddressInterface
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Is external address
     *
     * @return bool
     */
    public function isExternalAddress(): bool
    {
        return ($this->_getData(self::EXTERNAL_ADDRESS) ?? false);
    }

    /**
     * Set is external address
     *
     * @param bool $isExternalAddress
     * @return $this
     */
    public function setIsExternalAddress(bool $isExternalAddress): AddressInterface
    {
        return $this->setData(self::EXTERNAL_ADDRESS, $isExternalAddress);
    }
}
