<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Plugin\Model;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogStaging\Model\AvailabilityProcessorInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;

/**
 * Class Product is responsible for resolving availability for products on staging preview
 */
class Product
{
    /**
     * @var array
     */
    private $availabilityProcessors;

    /**
     * @param array $availabilityProcessors
     */
    public function __construct(
        $availabilityProcessors = []
    ) {
        $this->availabilityProcessors = $availabilityProcessors;
    }

    /**
     * Resolves availability for product on staging preview
     *
     * @param CatalogProduct $product
     * @param bool $result
     * @return bool
     */
    public function afterIsAvailable(CatalogProduct $product, bool $result) : bool
    {
        if ($product->getData('created_in') > strtotime('now')
            && (int) $product->getStatus() === Status::STATUS_ENABLED
            && $product->isInStock()
        ) {
            foreach ($this->availabilityProcessors as $processor) {
                if (!$processor instanceof AvailabilityProcessorInterface) {
                    throw new ConfigurationMismatchException(
                        __(
                            '%1 should implement %2',
                            get_class($processor),
                            AvailabilityProcessorInterface::class
                        )
                    );
                }
                $processorResult = $processor->execute($product, $result);
                if ($processorResult !== AvailabilityProcessorInterface::NOT_RELEVANT) {
                    return (bool)$processorResult;
                }
            }

            return true;
        }

        return $result;
    }
}
