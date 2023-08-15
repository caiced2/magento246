<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Api\Data;

/**
 * Account address
 *
 * @api
 */
interface AddressInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    public const ID = 'id';
    public const REGION = 'region';
    public const REGION_ID = 'region_id';
    public const REGION_CODE = 'region_code';
    public const COUNTRY_ID = 'country_id';
    public const STREET = 'street';
    public const EMAIL = 'email';
    public const TELEPHONE = 'telephone';
    public const POSTCODE = 'postcode';
    public const CITY = 'city';
    public const FIRSTNAME = 'firstname';
    public const LASTNAME = 'lastname';
    public const COMPANY = 'company';
    public const EXTERNAL_ADDRESS = 'external_address';

    /**#@-*/

    /**
     * Get id
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set id
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): AddressInterface;

    /**
     * Get region
     *
     * @return string|null
     */
    public function getRegion(): ?string;

    /**
     * Set region
     *
     * @param string|null $region
     * @return $this
     */
    public function setRegion(?string $region): AddressInterface;

    /**
     * Get region code
     *
     * @return string|null
     */
    public function getRegionCode(): ?string;

    /**
     * Set region code
     *
     * @param string|null $regionCode
     * @return $this
     */
    public function setRegionCode(?string $regionCode): AddressInterface;

    /**
     * Get region id
     *
     * @return string|null
     */
    public function getRegionId(): ?int;

    /**
     * Set region ID
     *
     * @param int|null $regionId
     * @return $this
     */
    public function setRegionId(?int $regionId): AddressInterface;

    /**
     * Two-letter country code in ISO_3166-2 format
     *
     * @return string
     */
    public function getCountryId(): string;

    /**
     * Set country id
     *
     * @param string $countryId
     * @return $this
     */
    public function setCountryId(string $countryId): AddressInterface;

    /**
     * Get street
     *
     * @return string[]|array
     */
    public function getStreet(): array;

    /**
     * Set street
     *
     * @param string[] $street
     * @return $this
     */
    public function setStreet(array $street): AddressInterface;

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Set email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): AddressInterface;

    /**
     * Get telephone number
     *
     * @return string
     */
    public function getTelephone(): string;

    /**
     * Set telephone number
     *
     * @param string $telephone
     * @return $this
     */
    public function setTelephone(string $telephone): AddressInterface;

    /**
     * Get postcode
     *
     * @return string
     */
    public function getPostcode(): string;

    /**
     * Set postcode
     *
     * @param string $postCode
     * @return $this
     */
    public function setPostcode(string $postCode): AddressInterface;

    /**
     * Get city name
     *
     * @return string
     */
    public function getCity(): string;

    /**
     * Set city name
     *
     * @param string $city
     * @return $this
     */
    public function setCity(string $city): AddressInterface;

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstname(): string;

    /**
     * Set first name
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstname(string $firstName): AddressInterface;

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastname(): string;

    /**
     * Set last name
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastname(string $lastName): AddressInterface;

    /**
     * Get company
     *
     * @return string
     */
    public function getCompany(): string;

    /**
     * Set company
     *
     * @param string $company
     * @return $this
     */
    public function setCompany(string $company): AddressInterface;

    /**
     * Is external address
     *
     * @return bool
     */
    public function isExternalAddress(): bool;

    /**
     * Set is external address
     *
     * @param bool $isExternalAddress
     * @return $this
     */
    public function setIsExternalAddress(bool $isExternalAddress): AddressInterface;
}
