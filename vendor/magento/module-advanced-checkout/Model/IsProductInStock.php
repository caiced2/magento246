<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Determine whether product is in stock using CatalogInventory set of APIs.
 */
class IsProductInStock implements IsProductInStockInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->productRepository = $productRepository;
    }

    /**
     * Check if product is in stock given Product id in a Website id.
     *
     * @param int $productId
     * @param int $websiteId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(int $productId, int $websiteId): bool
    {
        $product = $this->productRepository->getById($productId);
        $stockItem = $this->stockRegistry->getStockItem($productId, $websiteId);
        if ($product->isComposite()) {
            if (!$stockItem->getIsInStock()) {
                return false;
            }
            $productsByGroups = $product->getTypeInstance()->getProductsToPurchaseByReqGroups($product);
            foreach ($productsByGroups as $productsInGroup) {
                foreach ($productsInGroup as $childProduct) {
                    $childStockItem = $this->stockRegistry->getStockItem($childProduct->getId(), $websiteId);
                    if ($childStockItem->getIsInStock() && !$childProduct->isDisabled()) {
                        return true;
                    }
                }
            }
            return false;
        }

        return $stockItem->getIsInStock();
    }
}
