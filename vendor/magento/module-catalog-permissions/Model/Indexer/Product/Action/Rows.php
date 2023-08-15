<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Product\Action;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\AbstractAction;
use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\Generator;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\CatalogPermissions\Model\Indexer\Product\IndexFiller as ProductIndexFiller;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Rows extends AbstractAction
{
    /**
     * Limitation by products
     *
     * @var int[]
     */
    protected $entityIds;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ResourceConnection $resource
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param ConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param CatalogConfig $catalogConfig
     * @param CacheInterface $coreCache
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param Generator $batchQueryGenerator
     * @param ProductSelectDataProvider|null $productSelectDataProvider
     * @param ProductIndexFiller|null $productIndexFiller
     * @param TableMaintainer|null $tableMaintainer
     * @param ScopeConfigInterface|null $scopeConfig
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
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        Generator $batchQueryGenerator = null,
        ProductSelectDataProvider $productSelectDataProvider = null,
        ProductIndexFiller $productIndexFiller = null,
        TableMaintainer $tableMaintainer = null,
        ScopeConfigInterface $scopeConfig = null
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
            $productIndexFiller,
            null,
            $tableMaintainer
        );
        $this->scopeConfig = $scopeConfig
            ??ObjectManager::getInstance()->get(ScopeConfigInterface::class);
    }

    /**
     * Refresh entities index
     *
     * @param int[] $entityIds
     * @param bool $useIndexTempTable
     * @return void
     */
    public function execute(array $entityIds = [], $useIndexTempTable = false)
    {
        if ($entityIds) {
            $this->entityIds = $entityIds;
            $this->useIndexTempTable = $useIndexTempTable;

            $this->removeObsoleteIndexData();

            $this->reindex();
        }
    }

    /**
     * Run reindexation
     *
     * @return void
     */
    protected function reindex()
    {
        foreach ($this->getCustomerGroupIds() as $customerGroupId) {
            $this->populateProductIndex($customerGroupId);
            $this->fixProductPermissions();
        }
    }

    /**
     * Remove index entries before reindexation
     *
     * @return void
     */
    protected function removeObsoleteIndexData()
    {
        $productIndexTempTable = $this->getProductIndexTempTable();
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            foreach ($this->getCustomerGroupIds() as $groupId) {
                $productIndexTableName = $productIndexTempTable . '_' . $groupId;
                $this->connection->delete(
                    $productIndexTableName,
                    ['product_id IN (?)' => $this->entityIds]
                );
            }
        } else {
            $this->connection->delete(
                $productIndexTempTable,
                ['product_id IN (?)' => $this->entityIds]
            );
        }
    }

    /**
     * Check whether select ranging is needed
     *
     * @return bool
     */
    protected function isRangingNeeded()
    {
        return false;
    }

    /**
     * Return list of product IDs to reindex
     *
     * @return int[]
     */
    protected function getProductList()
    {
        return $this->entityIds;
    }

    /**
     * Retrieve category list
     *
     * Returns [entity_id, path] pairs.
     *
     * @return array
     */
    protected function getCategoryList()
    {
        return [];
    }
}
