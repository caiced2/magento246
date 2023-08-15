<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Api\Data;

/**
 * Gift Card data
 *
 * @codeCoverageIgnore
 * @api
 * @since 101.0.0
 */
interface GiftCardInterface
{
    /**
     * Get Id
     *
     * @return int
     * @since 101.0.0
     */
    public function getId();

    /**
     * Set Id
     *
     * @param int $id
     * @return $this
     * @since 101.0.0
     */
    public function setId($id);

    /**
     * Get Code
     *
     * @return string
     * @since 101.0.0
     */
    public function getCode();

    /**
     * Set Code
     *
     * @param string $code
     * @return $this
     * @since 101.0.0
     */
    public function setCode($code);

    /**
     * Get Amount
     *
     * @return float
     * @since 101.0.0
     */
    public function getAmount();

    /**
     * Set Amount
     *
     * @param float $amount
     * @return $this
     * @since 101.0.0
     */
    public function setAmount($amount);

    /**
     * Get Base Amount
     *
     * @return float
     * @since 101.0.0
     */
    public function getBaseAmount();

    /**
     * Set Base Amount
     *
     * @param float $baseAmount
     * @return $this
     * @since 101.0.0
     */
    public function setBaseAmount($baseAmount);
}
