<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Data;

use Magento\Framework\DataObject;
use Magento\QuickCheckout\Api\Data\AccountInterface;
use Magento\QuickCheckout\Api\Data\AddressInterface;
use Magento\QuickCheckout\Api\Data\PaymentMethodInterface;

/**
 * Account data object
 */
class Account extends DataObject implements AccountInterface
{
    /**
     * Get addresses
     *
     * @return AddressInterface[]
     */
    public function getAddresses(): array
    {
        return ($this->getData(self::ADDRESSES) ?? []);
    }

    /**
     * Set addresses
     *
     * @param AddressInterface[] $addresses
     * @return $this
     */
    public function setAddresses(array $addresses): AccountInterface
    {
        return $this->setData(self::ADDRESSES, $addresses);
    }

    /**
     * Get payment methods
     *
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods(): array
    {
        return ($this->getData(self::PAYMENT_METHODS) ?? []);
    }

    /**
     * Set payment methods
     *
     * @param PaymentMethodInterface[] $paymentMethods
     * @return AccountInterface
     */
    public function setPaymentMethods(array $paymentMethods): AccountInterface
    {
        return $this->setData(self::PAYMENT_METHODS, $paymentMethods);
    }

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return ($this->_getData(self::FIRST_NAME) ?? '');
    }

    /**
     * Set first name
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstName(string $firstName): AccountInterface
    {
        return $this->setData(self::FIRST_NAME, $firstName);
    }

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastName(): string
    {
        return ($this->_getData(self::LAST_NAME) ?? '');
    }

    /**
     * Set last name
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastName(string $lastName): AccountInterface
    {
        return $this->setData(self::LAST_NAME, $lastName);
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
    public function setEmail(string $email): AccountInterface
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Is default shipping address changed
     *
     * @return bool
     */
    public function isDefaultAddressChanged(): bool
    {
        return ($this->_getData(self::DEFAULT_ADDRESS_CHANGED) ?? false);
    }

    /**
     * Set default shipping address changed
     *
     * @param bool $defaultAddressChanged
     * @return $this
     */
    public function setDefaultAddressChanged(bool $defaultAddressChanged): AccountInterface
    {
        return $this->setData(self::DEFAULT_ADDRESS_CHANGED, $defaultAddressChanged);
    }
}
