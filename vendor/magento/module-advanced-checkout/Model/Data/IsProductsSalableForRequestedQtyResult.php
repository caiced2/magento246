<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Model\Data;

/**
 * DTO represents result for specific SKU for Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQtyInterface
 */
class IsProductsSalableForRequestedQtyResult
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var bool
     */
    private $isSalable;

    /**
     * @param string $sku
     * @param bool $isSalable
     */
    public function __construct(
        string $sku,
        bool $isSalable
    ) {
        $this->sku = $sku;
        $this->isSalable = $isSalable;
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
     * Is product salable
     *
     * @return bool
     */
    public function isSalable(): bool
    {
        return $this->isSalable;
    }
}
