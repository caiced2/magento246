<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedProductStaging\Model;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\CatalogStaging\Model\AvailabilityProcessorInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

/**
 * @inheritDoc
 */
class GroupedProductAvailabilityProcessor implements AvailabilityProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function execute(CatalogProduct $product, bool $originalProductAvailability): int
    {
        if ($product->getTypeInstance() instanceof Grouped) {
            if ($product->getQuantityAndStockStatus()['is_in_stock'] !== false) {
                foreach ($product->getTypeInstance()->getAssociatedProducts($product) as $simpleProduct) {
                    if ($simpleProduct->isSalable()) {
                        return self::IS_AVAILABLE;
                    }
                }
            }

            return self::IS_NOT_AVAILABLE;
        }

        return self::NOT_RELEVANT;
    }
}
