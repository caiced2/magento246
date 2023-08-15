<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Category\Action;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\CatalogPermissions\Model\Indexer\CustomerGroupFilter;
use Magento\CatalogPermissions\Model\Indexer\Product\Action\ProductSelectDataProvider;
use Magento\CatalogPermissions\Model\Indexer\Product\IndexFiller as ProductIndexFiller;
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Query\Generator;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class responsible for partial reindex of category permissions.
 *
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Rows extends \Magento\CatalogPermissions\Model\Indexer\AbstractAction
{
    /**
     * Limitation by categories
     *
     * @var int[]
     */
    protected $entityIds;

    /**
     * Affected product IDs
     *
     * @var int[]
     */
    protected $productIds;

    /**
     * @var \Magento\CatalogPermissions\Helper\Index
     */
    protected $helper;

    /**
     * @var CustomerGroupFilter
     */
    private $customerGroupFilter;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param ConfigInterface $config
     * @param StoreManagerInterface $storeManager
     * @param CatalogConfig $catalogConfig
     * @param CacheInterface $coreCache
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param \Magento\CatalogPermissions\Helper\Index $helper
     * @param Generator $batchQueryGenerator
     * @param ProductSelectDataProvider|null $productSelectDataProvider
     * @param CustomerGroupFilter|null $customerGroupFilter
     * @param ProductIndexFiller|null $productIndexFiller
     * @param TableMaintainer|null $tableMaintainer
     * @param ScopeConfigInterface|null $scopeConfig
     * @throws \Exception
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        WebsiteCollectionFactory $websiteCollectionFactory,
        GroupCollectionFactory $groupCollectionFactory,
        ConfigInterface $config,
        StoreManagerInterface $storeManager,
        CatalogConfig $catalogConfig,
        CacheInterface $coreCache,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\CatalogPermissions\Helper\Index $helper,
        Generator $batchQueryGenerator = null,
        ProductSelectDataProvider $productSelectDataProvider = null,
        CustomerGroupFilter $customerGroupFilter = null,
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
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig
            ??ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->customerGroupFilter = $customerGroupFilter
            ?: ObjectManager::getInstance()->get(CustomerGroupFilter::class);
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
            $this->connection->beginTransaction();
            try {
                if ($this->customerGroupFilter->getGroupIds()) {
                    $this->customerGroupIds = $this->customerGroupFilter->getGroupIds();
                    $this->removeIndexDataByCustomerGroupIds($this->customerGroupIds);
                } else {
                    $this->removeObsoleteIndexData();
                }
                $this->reindex();
                $this->connection->commit();
            } catch (\Exception $exception) {
                $this->connection->rollBack();
                throw $exception;
            }
        }

        $this->cleanCache();
    }

    /**
     * Remove index entries before reindexation
     *
     * @return void
     */
    protected function removeObsoleteIndexData()
    {
        $this->entityIds = array_merge($this->entityIds, $this->helper->getChildCategories($this->entityIds));
        $indexTempTable = $this->getIndexTempTable();
        $productIndexTempTable = $this->getProductIndexTempTable();
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            foreach ($this->getCustomerGroupIds() as $groupId) {
                $indexTableName =  $indexTempTable . '_' . $groupId;
                $productIndexTableName = $productIndexTempTable . '_' . $groupId;
                $this->removeIndexData($indexTableName, $productIndexTableName);
            }
        } else {
            $this->removeIndexData($indexTempTable, $productIndexTempTable);
        }
    }

    /**
     * Remove Index entries
     *
     * @param string $indexTempTable
     * @param string $productIndexTempTable
     * @return void
     */
    private function removeIndexData(string $indexTempTable, string $productIndexTempTable)
    {
        $this->connection->delete(
            $indexTempTable,
            ['category_id IN (?)' => $this->entityIds]
        );
        $this->connection->delete(
            $productIndexTempTable,
            ['product_id IN (?)' => $this->getProductList()]
        );
    }

    /**
     * Remove index entries by customer group and categories before reindexation
     *
     * @param array $groupIds
     * @return self
     */
    private function removeIndexDataByCustomerGroupIds(array $groupIds): self
    {
        $this->entityIds = array_merge($this->entityIds, $this->helper->getChildCategories($this->entityIds));
        $this->connection->delete(
            $this->getIndexTempTable(),
            sprintf(
                'customer_group_id IN (%s) AND category_id IN (%s)',
                implode(',', $groupIds),
                implode(',', $this->entityIds)
            )
        );
        if ($this->getProductList()) {
            $this->connection->delete(
                $this->getProductIndexTempTable(),
                sprintf(
                    'customer_group_id IN (%s) AND product_id IN (%s)',
                    implode(',', $groupIds),
                    implode(',', $this->getProductList())
                )
            );
        }

        return $this;
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
        return $this->helper->getCategoryList($this->entityIds);
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
        if ($this->productIds === null) {
            $this->productIds = $this->helper->getProductList($this->entityIds);
        }
        return $this->productIds;
    }
}
