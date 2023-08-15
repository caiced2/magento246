<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model;

use Magento\Catalog\Model\Product as CatalogProduct;

/**
 * Interface for resolving availability on staging preview for different types of products
 */
interface AvailabilityProcessorInterface
{
    const IS_NOT_AVAILABLE = 0;

    const IS_AVAILABLE = 1;

    const NOT_RELEVANT = 2;

    /**
     * Resolves availability for different types of products on staging preview
     *
     * @param CatalogProduct $product
     * @param bool $originalProductAvailability
     * @return int
     */
    public function execute(
        CatalogProduct $product,
        bool $originalProductAvailability
    ): int;
}
