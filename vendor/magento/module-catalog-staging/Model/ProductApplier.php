<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model;

use Magento\Catalog\Model\Indexer\Product\Category;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogStaging\Helper\ReindexPool;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Staging\Model\StagingApplierInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class ProductApplier
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductApplier implements StagingApplierInterface
{
    /**
     * @var Collection
     */
    protected $productCollectionResource;

    /**
     * @var ReindexPool
     */
    protected $indexerPool;

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var CacheContext
     */
    protected $cacheContext;

    /**
     * @param Collection $productResource
     * @param ReindexPool $indexerPool
     * @param IndexerRegistry $indexerRegistry
     * @param CacheContext $cacheContext
     */
    public function __construct(
        Collection $productResource,
        ReindexPool $indexerPool,
        IndexerRegistry $indexerRegistry,
        CacheContext $cacheContext
    ) {
        $this->productCollectionResource = $productResource;
        $this->indexerPool = $indexerPool;
        $this->indexerRegistry = $indexerRegistry;
        $this->cacheContext = $cacheContext;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $entityIds)
    {
        if ($entityIds) {
            $this->indexerPool->reindexList($entityIds);

            $select = $this->productCollectionResource->getSelect()->reset()
                ->distinct(true)
                ->from($this->productCollectionResource->getTable('catalog_category_product'), ['category_id'])
                ->where('product_id IN (?)', $entityIds);
            $affectedCategories = $this->productCollectionResource->getConnection()->fetchCol($select);

            $this->cacheContext->registerEntities(\Magento\Catalog\Model\Category::CACHE_TAG, $affectedCategories);
            $this->cacheContext->registerEntities(\Magento\Catalog\Model\Product::CACHE_TAG, $entityIds);
        }
    }
}
