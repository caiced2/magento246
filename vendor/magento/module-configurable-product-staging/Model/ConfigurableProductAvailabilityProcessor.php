<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableProductStaging\Model;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\CatalogStaging\Model\AvailabilityProcessorInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * @inheritDoc
 */
class ConfigurableProductAvailabilityProcessor implements AvailabilityProcessorInterface
{
    /**
     * @inheritDoc
     */
    public function execute(CatalogProduct $product, bool $originalProductAvailability): int
    {
        if ($product->getTypeInstance() instanceof Configurable) {
            foreach ($product->getTypeInstance()->getUsedProducts($product) as $simpleProduct) {
                if ($simpleProduct->isSalable()) {
                    return self::IS_AVAILABLE;
                }
            }

            return self::IS_NOT_AVAILABLE;
        }

        return self::NOT_RELEVANT;
    }
}
