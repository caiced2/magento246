<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Model;

/**
 * Service which detects whether given products quantities are salable for a given stock for a given Website id
 *
 * @api
 */
interface AreProductsSalableForRequestedQtyInterface
{
    /**
     * Get whether products are salable in requested Qty for given set of SKUs in specified website.
     *
     * @param \Magento\AdvancedCheckout\Model\Data\ProductQuantity[] $productQuantities
     * @param int $websiteId
     * @return \Magento\AdvancedCheckout\Model\Data\IsProductsSalableForRequestedQtyResult[]
     */
    public function execute(array $productQuantities, int $websiteId): array;
}
