<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Model\Data;

/**
 * DTO represents product and quantity for \Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQtyInterface
 */
class ProductQuantity
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var float
     */
    private $qty;

    /**
     * @param string $sku
     * @param float $qty
     */
    public function __construct(string $sku, float $qty)
    {
        $this->sku = $sku;
        $this->qty = $qty;
    }

    /**
     * Get product SKU
     *
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * Get product qty
     *
     * @return float
     */
    public function getQty(): float
    {
        return $this->qty;
    }
}
