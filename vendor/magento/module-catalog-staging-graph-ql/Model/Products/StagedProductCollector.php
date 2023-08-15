<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStagingGraphQl\Model\Products;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Collect list of staged products by SKU
 */
class StagedProductCollector
{
    /**
     * @var array
     */
    private $stagedProductSkus = [];

    /**
     * @var array
     */
    private $stagedProductIds = [];

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param MetadataPool $metadataPool
     */
    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * Add a product sku to list of staged products
     *
     * @param string $sku
     */
    public function addProductSku(string $sku)
    {
        $this->stagedProductSkus[$sku] = true;
    }

    /**
     * Add a product ID to list of staged products
     *
     * @param int $id
     */
    public function addProductId(int $id)
    {
        $this->stagedProductIds[$id] = true;
    }

    /**
     * Check if a product is in list of staged products
     *
     * @param Product $product
     * @return bool
     */
    public function productIsStaged(Product $product): bool
    {
        $sku = $product->getSku();
        $id = $product->getData($this->getProductLinkIdFieldName());
        $skuStaged = $this->stagedProductSkus[$sku] ?? false;
        $idStaged = $this->stagedProductIds[$id] ?? false;
        return  $skuStaged || $idStaged;
    }

    /**
     * Get field used to link product ids
     *
     * This is the field used to relate product, e.g. entity_id, row_id
     *
     * @return string
     */
    private function getProductLinkIdFieldName(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }
}
