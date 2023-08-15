<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Model\Entity;

use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;

class AsyncOrderMessage implements AsyncOrderMessageInterface
{
    private $orderId;

    private $incrementId;

    private $cartId;

    private $isGuest;

    private $paymentMethod;

    private $address;

    private $email;

    private $additionalData;

    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @inheritdoc
     */
    public function getCartId()
    {
        return $this->cartId;
    }

    /**
     * @inheritdoc
     */
    public function setIncrementId($id): void
    {
        $this->incrementId = $id;
    }

    /**
     * @inheritdoc
     */
    public function getIncrementId()
    {
        return $this->incrementId;
    }

    /**
     * @inheritdoc
     */
    public function setCartId($cartId): void
    {
        $this->cartId = $cartId;
    }

    /**
     * @inheritdoc
     */
    public function getIsGuest()
    {
        return (bool) $this->isGuest;
    }

    /**
     * @inheritdoc
     */
    public function setIsGuest($isGuest): void
    {
        $this->isGuest = $isGuest;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @inheritdoc
     */
    public function setPaymentMethod(PaymentInterface $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @inheritdoc
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @inheritdoc
     */
    public function setAddress(AddressInterface $address): void
    {
        $this->address = $address;
    }

    /**
     * @inheritdoc
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @inheritdoc
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * @inheritdoc
     */
    public function setAdditionalData($additionalData): void
    {
        $this->additionalData = $additionalData;
    }
}
