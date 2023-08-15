<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Indexer\Category\Product;

use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Search\Request\IndexScopeResolverInterface as TableResolver;

class Preview extends AbstractAction
{
    /**
     * @var int|null
     */
    protected $categoryId;

    /**
     * @var array
     */
    protected $productIds = [];

    /**
     * Prefix for temporary table name
     */
    public const TMP_PREFIX = '_catalog_staging_tmp';

    /**
     * @var TableResolver
     */
    private $tableResolver;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Config $config
     * @param QueryGenerator $queryGenerator
     * @param MetadataPool|null $metadataPool
     * @param TableMaintainer|null $tableMaintainer
     * @param TableResolver|null $tableResolver
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Config $config,
        QueryGenerator $queryGenerator = null,
        MetadataPool $metadataPool = null,
        TableMaintainer $tableMaintainer = null,
        TableResolver $tableResolver = null
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($resource, $storeManager, $config, $queryGenerator, $metadataPool, $tableMaintainer);
        $this->tableResolver = $tableResolver ?: ObjectManager::getInstance()->get(TableResolver::class);
    }

    /**
     * Executes preview indexers
     *
     * @param int|null $categoryId
     * @param array $productIds
     * @return void
     *
     * @deprecated
     * @see executeScoped()
     */
    public function execute($categoryId = null, array $productIds = [])
    {
        $this->categoryId = $categoryId;
        $this->productIds = $productIds;
        $this->prepareTemporaryStorage();
        $this->reindex();
    }

    /**
     * Executes preview indexers
     *
     * @param int|null $categoryId
     * @param int|null $storeId
     * @return void
     */
    public function executeScoped($categoryId = null, int $storeId = null)
    {
        $this->categoryId = $categoryId;
        $this->prepareTemporaryStorage($storeId);
        $this->reindex($storeId);
    }

    /**
     * Run reindexation
     *
     * @param int|null $storeId
     * @return void
     * @throws NoSuchEntityException
     */
    protected function reindex(int $storeId = null): void
    {
        $store = $this->storeManager->getStore($storeId);
        if ($this->getPathFromCategoryId($store->getRootCategoryId())) {
            $this->reindexRootCategory($store);
            $this->reindexAnchorCategories($store);
            $this->reindexNonAnchorCategories($store);
        }
    }

    /**
     * Get temporary index table name
     *
     * @param int $storeId
     * @return string
     */
    public function getTemporaryTable($storeId)
    {
        $catalogCategoryProductDimension = new Dimension(\Magento\Store\Model\Store::ENTITY, $storeId);

        $indexTable = $this->tableResolver->resolve(
            AbstractAction::MAIN_INDEX_TABLE,
            [
                $catalogCategoryProductDimension
            ]
        );

        return $indexTable . static::TMP_PREFIX;
    }

    /**
     * Creates temporary table
     *
     * @param int|null $storeId
     * @return void
     */
    protected function prepareTemporaryStorage(int $storeId = null): void
    {
        $storeId = $storeId ?: $this->storeManager->getStore()->getId();
        $catalogCategoryProductDimension = new Dimension(\Magento\Store\Model\Store::ENTITY, $storeId);

        $indexTable = $this->tableResolver->resolve(
            AbstractAction::MAIN_INDEX_TABLE,
            [
                $catalogCategoryProductDimension
            ]
        );

        $this->resource->getConnection()->createTemporaryTableLike(
            $this->resource->getTableName($this->getTemporaryTable($storeId)),
            $indexTable
        );
    }

    /**
     * Return index table name
     *
     * @param int $storeId
     * @return string
     */
    protected function getIndexTable($storeId): string
    {
        return $this->getTemporaryTable($storeId);
    }

    /**
     * Builds select to get all products for a given store
     *
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function getAllProducts(\Magento\Store\Model\Store $store)
    {
        $allProductsSelect = parent::getAllProducts($store);
        $allProductsSelect->where('ccp.category_id IN (?)', $this->categoryId);
        return $allProductsSelect;
    }

    /**
     * Builds select for anchor categories for a given store
     *
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function createAnchorSelect(\Magento\Store\Model\Store $store)
    {
        $anchorSelect = parent::createAnchorSelect($store);
        $anchorSelect->where('cc.entity_id IN (?)', $this->categoryId);
        return $anchorSelect;
    }

    /**
     * Builds select for non anchor categories for a given store
     *
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function getNonAnchorCategoriesSelect(\Magento\Store\Model\Store $store)
    {
        $nonAnchorSelect = parent::getNonAnchorCategoriesSelect($store);
        $nonAnchorSelect->where('cc.entity_id IN (?)', $this->categoryId);
        return $nonAnchorSelect;
    }
}
