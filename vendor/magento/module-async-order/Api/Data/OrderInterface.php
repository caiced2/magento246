<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AsyncOrder\Api\Data;

/**
 * Initial async order interface.
 * Order that is initially placed by async order with minimal set of attributes.
 */
interface OrderInterface
{
    /**
     * Sets entity ID.
     *
     * @param int $entityId
     * @return OrderInterface
     */
    public function setEntityId($entityId);

    /**
     * Gets the ID for the order.
     *
     * @return int|null Order ID.
     */
    public function getEntityId();

    /**
     * Sets the increment ID for the order.
     *
     * @param string $id
     * @return OrderInterface
     */
    public function setIncrementId($id);

    /**
     * Gets the increment ID for the order.
     *
     * @return string|null Increment ID.
     */
    public function getIncrementId();

    /**
     * Sets the store ID for the order.
     *
     * @param int $id
     * @return OrderInterface
     */
    public function setStoreId($id);

    /**
     * Gets the store ID for the order.
     *
     * @return int|null Store ID.
     */
    public function getStoreId();

    /**
     * Sets the status for the order.
     *
     * @param string $status
     * @return OrderInterface
     */
    public function setStatus($status);

    /**
     * Gets the status for the order.
     *
     * @return string|null Status.
     */
    public function getStatus();

    /**
     * Sets the customer ID for the order.
     *
     * @param int $id
     * @return OrderInterface
     */
    public function setCustomerId($id);

    /**
     * Gets the customer ID for the order.
     *
     * @return int|null Customer ID.
     */
    public function getCustomerId();

    /**
     * Sets the grand total for the order.
     *
     * @param float $amount
     * @return OrderInterface
     */
    public function setGrandTotal($amount);

    /**
     * Gets the grand total for the order.
     *
     * @return float Grand total.
     */
    public function getGrandTotal();

    /**
     * Sets the customer email address for the order.
     *
     * @param string $email
     * @return OrderInterface
     */
    public function setCustomerEmail($email);

    /**
     * Gets the customer email address for the order.
     *
     * @return null|string Customer email address.
     */
    public function getCustomerEmail();

    /**
     * Sets the quote ID for the order.
     *
     * @param int $id
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setQuoteId($id);

    /**
     * Gets the quote ID for the order.
     *
     * @return int|null Quote ID.
     */
    public function getQuoteId();

    /**
     * Sets the total item count for the order.
     *
     * @param int $totalItemCount
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setTotalItemCount($totalItemCount);

    /**
     * Gets the total item count for the order.
     *
     * @return int|null Total item count.
     */
    public function getTotalItemCount();

    /**
     * Sets the protect code for the order.
     *
     * @param string $code
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setProtectCode($code);

    /**
     * Gets the protect code for the order.
     *
     * @return string|null Protect code.
     */
    public function getProtectCode();

    /**
     * Sets the base currency code for the order.
     *
     * @param string $code
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setBaseCurrencyCode($code);

    /**
     * Gets the base currency code for the order.
     *
     * @return string|null Base currency code.
     */
    public function getBaseCurrencyCode();

    /**
     * Sets the global currency code for the order.
     *
     * @param string $code
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setGlobalCurrencyCode($code);

    /**
     * Gets the global currency code for the order.
     *
     * @return string|null Global currency code.
     */
    public function getGlobalCurrencyCode();

    /**
     * Sets the order currency code for the order.
     *
     * @param string $code
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setOrderCurrencyCode($code);

    /**
     * Gets the order currency code for the order.
     *
     * @return string|null Order currency code.
     */
    public function getOrderCurrencyCode();

    /**
     * Sets the store currency code for the order.
     *
     * @param string $code
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function setStoreCurrencyCode($code);

    /**
     * Gets the store currency code for the order.
     *
     * @return string|null Store currency code.
     */
    public function getStoreCurrencyCode();
}
