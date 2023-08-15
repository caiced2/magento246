<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Category\Action;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\ResourceModel\Indexer\ActiveTableSwitcher;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\Product\IndexFiller as ProductIndexFiller;
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogPermissions\Model\Indexer\Product\Action\ProductSelectDataProvider;
use Magento\Framework\DB\Query\Generator;

/**
 * Action for full reindex
 *
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Full extends \Magento\CatalogPermissions\Model\Indexer\AbstractAction
{
    /**
     * @var ActiveTableSwitcher
     */
    private $activeTableSwitcher;

    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * @param ResourceConnection $resource
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param ConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param CatalogConfig $catalogConfig
     * @param CacheInterface $coreCache
     * @param MetadataPool $metadataPool
     * @param ActiveTableSwitcher $activeTableSwitcher
     * @param Generator $batchQueryGenerator
     * @param ProductSelectDataProvider|null $productSelectDataProvider
     * @param ProductIndexFiller|null $productIndexFiller
     * @param TableMaintainer|null $tableMaintainer
     * @throws \Exception
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceConnection $resource,
        WebsiteCollectionFactory $websiteCollectionFactory,
        GroupCollectionFactory $groupCollectionFactory,
        ConfigInterface $config,
        StoreManagerInterface $storeManager,
        CatalogConfig $catalogConfig,
        CacheInterface $coreCache,
        MetadataPool $metadataPool,
        ActiveTableSwitcher $activeTableSwitcher,
        Generator $batchQueryGenerator = null,
        ProductSelectDataProvider $productSelectDataProvider = null,
        ProductIndexFiller $productIndexFiller = null,
        TableMaintainer $tableMaintainer = null
    ) {
        parent::__construct(
            $resource,
            $websiteCollectionFactory,
            $groupCollectionFactory,
            $config,
            $storeManager,
            $catalogConfig,
            $coreCache,
            $metadataPool,
            $batchQueryGenerator,
            $productSelectDataProvider,
            $productIndexFiller
        );
        $this->activeTableSwitcher = $activeTableSwitcher;
        $this->tableMaintainer = $tableMaintainer ?? ObjectManager::getInstance()->get(TableMaintainer::class);
    }

    /**
     * Return category index table name
     *
     * @return string
     * @since 100.2.0
     */
    protected function getIndexTable()
    {
        return $this->activeTableSwitcher->getAdditionalTableName($this->getTable(self::INDEX_TABLE));
    }

    /**
     * Return product index table
     *
     * @return string
     * @since 100.2.0
     */
    protected function getProductIndexTable()
    {
        return $this->activeTableSwitcher->getAdditionalTableName(parent::getIndexTable() . self::PRODUCT_SUFFIX);
    }

    /**
     * Refresh entities index
     *
     * @return void
     */
    public function execute()
    {
        $this->useIndexTempTable = false;
        $this->tableMaintainer->clearIndexTempTable();
        $this->reindex();
        $this->activeTableSwitcher->switchTable(
            $this->connection,
            $this->tableMaintainer->getAllCategoryTablesForCustomerGroups()
        );
        $this->activeTableSwitcher->switchTable(
            $this->connection,
            $this->tableMaintainer->getAllProductsTablesForCustomerGroups()
        );
        $this->cleanCache();
    }

    /**
     * Retrieve category list
     *
     * Return entity_id, path pairs.
     *
     * @return array
     */
    protected function getCategoryList()
    {
        $select = $this->connection->select()->from(
            $this->getTable('catalog_category_entity'),
            ['entity_id', 'path']
        )->order(
            'level ASC'
        );

        return $this->connection->fetchPairs($select);
    }

    /**
     * Check whether select ranging is needed
     *
     * @return bool
     */
    protected function isRangingNeeded()
    {
        return true;
    }

    /**
     * Return list of product IDs to reindex
     *
     * @return int[]
     */
    protected function getProductList()
    {
        return [];
    }
}
