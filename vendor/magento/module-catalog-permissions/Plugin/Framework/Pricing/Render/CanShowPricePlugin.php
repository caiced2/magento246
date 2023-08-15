<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Plugin\Framework\Pricing\Render;

use Magento\Framework\Pricing\Render\PriceBox;

/**
 * Modifies PriceBox cache key based on the price displaying allowance.
 */
class CanShowPricePlugin
{
    /**
     * Modify PriceBox cache key.
     *
     * @param PriceBox $subject
     * @param string $result
     * @return string
     */
    public function afterGetCacheKey(PriceBox $subject, string $result): string
    {
        return sprintf(
            '%s-%s',
            $result,
            $subject->getSaleableItem()->getCanShowPrice() !== false ? 'allow_price' : 'deny_price'
        );
    }
}
