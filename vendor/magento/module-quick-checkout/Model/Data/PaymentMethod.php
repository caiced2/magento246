<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Data;

use Magento\Framework\DataObject;
use Magento\QuickCheckout\Api\Data\AddressInterface;
use Magento\QuickCheckout\Api\Data\PaymentMethodInterface;

/**
 * Account payment data object
 */
class PaymentMethod extends DataObject implements PaymentMethodInterface
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
    public function setId(string $id): PaymentMethodInterface
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType(): string
    {
        return ($this->_getData(self::TYPE) ?? '');
    }

    /**
     * Set type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): PaymentMethodInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * Get last four digits of card
     *
     * @return string
     */
    public function getLast4(): string
    {
        return ($this->_getData(self::LAST4) ?? '');
    }

    /**
     * Set last four digits of card
     *
     * @param string $last4
     * @return $this
     */
    public function setLast4(string $last4): PaymentMethodInterface
    {
        return $this->setData(self::LAST4, $last4);
    }

    /**
     * Get card network
     *
     * @return string
     */
    public function getNetwork(): string
    {
        return ($this->_getData(self::NETWORK) ?? '');
    }

    /**
     * Set card network
     *
     * @param string $network
     * @return $this
     */
    public function setNetwork(string $network): PaymentMethodInterface
    {
        return $this->setData(self::NETWORK, $network);
    }

    /**
     * Get billing address of payment
     *
     * @return \Magento\QuickCheckout\Api\Data\AddressInterface|null
     */
    public function getBillingAddress(): ?AddressInterface
    {
        return $this->_getData(self::BILLING_ADDRESS);
    }

    /**
     * Set billing address of payment
     *
     * @param \Magento\QuickCheckout\Api\Data\AddressInterface $billingAddress
     * @return $this
     */
    public function setBillingAddress(
        \Magento\QuickCheckout\Api\Data\AddressInterface $billingAddress
    ): PaymentMethodInterface {
        return $this->setData(self::BILLING_ADDRESS, $billingAddress);
    }

    /**
     * Get expiration month of the card
     *
     * @return string
     */
    public function getExpirationMonth(): string
    {
        return ($this->_getData(self::EXPIRATION_MONTH) ?? '');
    }

    /**
     * Set expiration month of the card
     *
     * @param string $expirationMonth
     * @return PaymentMethodInterface
     */
    public function setExpirationMonth(string $expirationMonth): PaymentMethodInterface
    {
        return $this->setData(self::EXPIRATION_MONTH, $expirationMonth);
    }

    /**
     * Get expiration year of the card
     *
     * @return string
     */
    public function getExpirationYear(): string
    {
        return ($this->_getData(self::EXPIRATION_YEAR) ?? '');
    }

    /**
     * Set expiration year of the card
     *
     * @param string $expirationYear
     * @return PaymentMethodInterface
     */
    public function setExpirationYear(string $expirationYear): PaymentMethodInterface
    {
        return $this->setData(self::EXPIRATION_YEAR, $expirationYear);
    }
}
