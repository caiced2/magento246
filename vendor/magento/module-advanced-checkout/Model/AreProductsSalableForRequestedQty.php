<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Model;

use Magento\AdvancedCheckout\Model\Data\IsProductsSalableForRequestedQtyResultFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Service which detects whether given products quantities are salable for a given stock for a given Website id
 */
class AreProductsSalableForRequestedQty implements AreProductsSalableForRequestedQtyInterface
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var IsProductsSalableForRequestedQtyResultFactory
     */
    private $salableForRequestedQtyResultFactory;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param IsProductsSalableForRequestedQtyResultFactory $salableForRequestedQtyResultFactory
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        IsProductsSalableForRequestedQtyResultFactory $salableForRequestedQtyResultFactory
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->salableForRequestedQtyResultFactory = $salableForRequestedQtyResultFactory;
    }

    /**
     * Get whether products are salable in requested Qty for given set of SKUs in specified website.
     *
     * @param \Magento\AdvancedCheckout\Model\Data\ProductQuantity[] $productQuantities
     * @param int $websiteId
     * @return \Magento\AdvancedCheckout\Model\Data\IsProductsSalableForRequestedQtyResult[]
     */
    public function execute(array $productQuantities, int $websiteId): array
    {
        $result = [];
        foreach ($productQuantities as $productQuantity) {
            try {
                $isSalable = (bool)$this->stockRegistry->getProductStockStatusBySku(
                    $productQuantity->getSku(),
                    $websiteId
                );
            } catch (NoSuchEntityException $e) {
                $isSalable = false;
            }
            $result[] = $this->salableForRequestedQtyResultFactory->create(
                ['sku' => $productQuantity->getSku(), 'isSalable' => $isSalable]
            );
        }

        return $result;
    }
}
