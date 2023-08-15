<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Api\Data;

/**
 * Data account interface
 *
 * @api
 */
interface AccountInterface
{
    public const FIRST_NAME = 'first_name';
    public const LAST_NAME = 'last_name';
    public const EMAIL = 'email';
    public const ADDRESSES = 'addresses';
    public const PAYMENT_METHODS = 'payment_methods';
    public const DEFAULT_ADDRESS_CHANGED = 'default_address_changed';

    /**
     * Get addresses
     *
     * @return \Magento\QuickCheckout\Api\Data\AddressInterface[]
     */
    public function getAddresses(): array;

    /**
     * Set addresses
     *
     * @param \Magento\QuickCheckout\Api\Data\AddressInterface[] $addresses
     * @return $this
     */
    public function setAddresses(array $addresses): AccountInterface;

    /**
     * Get payment methods
     *
     * @return \Magento\QuickCheckout\Api\Data\PaymentMethodInterface[]
     */
    public function getPaymentMethods(): array;

    /**
     * Set payment methods
     *
     * @param \Magento\QuickCheckout\Api\Data\PaymentMethodInterface[] $paymentMethods
     * @return AccountInterface
     */
    public function setPaymentMethods(array $paymentMethods): AccountInterface;

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
    public function setEmail(string $email): AccountInterface;

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstName(): string;

    /**
     * Set first name
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstName(string $firstName): AccountInterface;

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastName(): string;

    /**
     * Set last name
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastName(string $lastName): AccountInterface;

    /**
     * Is shipping address changed
     *
     * @return bool
     */
    public function isDefaultAddressChanged(): bool;

    /**
     * Set default shipping address changed
     *
     * @param bool $defaultAddressChanged
     * @return $this
     */
    public function setDefaultAddressChanged(bool $defaultAddressChanged): AccountInterface;
}
