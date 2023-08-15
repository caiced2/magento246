<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleStaging\Model;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\CatalogStaging\Model\AvailabilityProcessorInterface;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;

/**
 * @inheritDoc
 */
class BundleProductAvailabilityProcessor implements AvailabilityProcessorInterface
{
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @param ProductFactory $productFactory
     */
    public function __construct(
        ProductFactory $productFactory
    ) {
        $this->productFactory = $productFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(CatalogProduct $product, bool $originalProductAvailability): int
    {
        if ($product->getTypeInstance() instanceof Bundle) {
            $isAvailableOptions = true;
            $childrenOptions = $product->getTypeInstance()->getChildrenIds($product->getId());

            foreach ($childrenOptions as $childrenOption) {
                $isAvailableChild = false;
                foreach ($childrenOption as $key => $childId) {
                    $simpleProduct = $this->productFactory->create()->load($childId);
                    if ((int) $simpleProduct->getStatus() === Status::STATUS_ENABLED
                        && $simpleProduct->getQuantityAndStockStatus()['is_in_stock']
                    ) {
                        $isAvailableChild = true;
                        break;
                    }
                }
                $isAvailableOptions = $isAvailableOptions && $isAvailableChild;
            }

            return (int) ($isAvailableOptions || ($product->getData('all_items_salable') === true));
        }

        return self::NOT_RELEVANT;
    }
}
