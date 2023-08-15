<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Api\Data;

/**
 * Account payment
 *
 * @api
 */
interface PaymentMethodInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    public const ID = 'id';
    public const TYPE = 'type';
    public const LAST4 = 'last4';
    public const NETWORK = 'network';
    public const BILLING_ADDRESS = 'billing_address';
    public const EXPIRATION_MONTH = 'expiration_month';
    public const EXPIRATION_YEAR = 'expiration_year';
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
    public function setId(string $id): PaymentMethodInterface;

    /**
     * Get type
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): PaymentMethodInterface;

    /**
     * Get last four digits of card
     *
     * @return string
     */
    public function getLast4(): string;

    /**
     * Set last four digits of card
     *
     * @param string $last4
     * @return $this
     */
    public function setLast4(string $last4): PaymentMethodInterface;

    /**
     * Get card network
     *
     * @return string
     */
    public function getNetwork(): string;

    /**
     * Set card network
     *
     * @param string $network
     * @return $this
     */
    public function setNetwork(string $network): PaymentMethodInterface;

    /**
     * Get billing address
     *
     * @return \Magento\QuickCheckout\Api\Data\AddressInterface|null
     */
    public function getBillingAddress(): ?\Magento\QuickCheckout\Api\Data\AddressInterface;

    /**
     * Set billing address
     *
     * @param \Magento\QuickCheckout\Api\Data\AddressInterface $billingAddress
     * @return $this
     */
    public function setBillingAddress(
        \Magento\QuickCheckout\Api\Data\AddressInterface $billingAddress
    ): PaymentMethodInterface;

    /**
     * Get expiration month of the card
     *
     * @return string
     */
    public function getExpirationMonth(): string;

    /**
     * Set expiration month of the card
     *
     * @param string $expirationMonth
     * @return $this
     */
    public function setExpirationMonth(string $expirationMonth): PaymentMethodInterface;

    /**
     * Get expiration year of the card
     *
     * @return string
     */
    public function getExpirationYear(): string;

    /**
     * Set expiration year of the card
     *
     * @param string $expirationYear
     * @return $this
     */
    public function setExpirationYear(string $expirationYear): PaymentMethodInterface;
}
