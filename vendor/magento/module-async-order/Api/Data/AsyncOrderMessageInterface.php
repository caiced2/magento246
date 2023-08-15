<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AsyncOrder\Api\Data;

/**
 * Interface AsyncOrderMessageInterface
 *
 */
interface AsyncOrderMessageInterface
{
    /**
     * Get orderId
     *
     * @return string
     */
    public function getOrderId();

    /**
     * Set orderId
     *
     * @param string $orderId
     * @return void
     */
    public function setOrderId($orderId): void;

    /**
     * Get incrementId
     *
     * @return string
     */
    public function getIncrementId();

    /**
     * Set incrementId
     *
     * @param string $id
     * @return void
     */
    public function setIncrementId($id): void;

    /**
     * Get cartId
     *
     * @return string
     */
    public function getCartId();

    /**
     * Set cartId
     *
     * @param string $cartId
     * @return void
     */
    public function setCartId($cartId): void;

    /**
     * Get isGuest
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsGuest();

    /**
     * Set isGuest
     *
     * @param bool $isGuest
     * @return void
     */
    public function setIsGuest($isGuest): void;

    /**
     * Get payment method
     *
     * @return \Magento\Quote\Api\Data\PaymentInterface|null
     */
    public function getPaymentMethod();

    /**
     * Set payment method
     *
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @return void
     */
    public function setPaymentMethod(\Magento\Quote\Api\Data\PaymentInterface $paymentMethod): void;

    /**
     * Get address
     *
     * @return \Magento\Quote\Api\Data\AddressInterface|null
     */
    public function getAddress();

    /**
     * Set address
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return void
     */
    public function setAddress(\Magento\Quote\Api\Data\AddressInterface $address): void;

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail();

    /**
     * Set email
     *
     * @param string $email
     * @return void
     */
    public function setEmail($email): void;

    /**
     * Get additional data
     *
     * @return string
     */
    public function getAdditionalData();

    /**
     * Set additional data
     *
     * @param string $additionalData
     * @return void
     */
    public function setAdditionalData($additionalData): void;
}
