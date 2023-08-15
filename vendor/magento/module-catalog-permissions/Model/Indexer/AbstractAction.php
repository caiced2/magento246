<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\CatalogPermissions\Model\Indexer\Product\IndexFiller as ProductIndexFiller;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Query\Generator;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Framework\App\CacheInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogPermissions\Model\Indexer\Product\Action\ProductSelectDataProvider;
use Magento\Indexer\Model\ProcessManager;

/**
 * Abstract class for CatalogPermission indexers
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * phpcs:disable Magento2.Classes.AbstractApi
 * @api
 * @since 100.0.2
 */
abstract class AbstractAction
{
    /**
     * Grant value for allow
     */
    public const GRANT_ALLOW = 1;

    /**
     * Grant value for deny
     */
    public const GRANT_DENY = 0;

    /**
     * Category index table name
     */
    public const INDEX_TABLE = 'magento_catalogpermissions_index';

    /**
     * Product index table name suffix
     */
    public const PRODUCT_SUFFIX = '_product';

    /**
     * Replica index table name suffix
     */
    public const REPLICA_SUFFIX = '_replica';

    /**
     * Suffix for index table to show it is temporary
     */
    public const TMP_SUFFIX = '_tmp';

    /**
     * Category chunk size
     */
    public const CATEGORY_STEP_COUNT = 500;

    /**
     * Product chunk size
     */
    public const PRODUCT_STEP_COUNT = 10000;

    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var WebsiteCollectionFactory
     */
    protected $websiteCollectionFactory;

    /**
     * @var GroupCollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var int[]
     */
    protected $websitesIds = [];

    /**
     * @var int[]
     */
    protected $customerGroupIds = [];

    /**
     * Whether to use index or temporary index table
     *
     * @var bool
     */
    protected $useIndexTempTable = true;

    /**
     * List of permissions prepared to insert into index
     *
     * @var array
     */
    protected $indexCategoryPermissions = [];

    /**
     * Grant values for permission inheritance
     *
     * @var array
     */
    protected $grantsInheritance = [
        'grant_catalog_category_view' => self::GRANT_ALLOW,
        'grant_catalog_product_price' => self::GRANT_ALLOW,
        'grant_checkout_items' => self::GRANT_ALLOW
    ];

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $catalogConfig;

    /**
     * @var CacheInterface
     */
    protected $coreCache;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var \Magento\Framework\EntityManager\EntityMetadata
     * @since 100.1.0
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\EntityManager\EntityMetadata
     * @since 100.1.0
     */
    protected $categoryMetadata;

    /**
     * @var ProductSelectDataProvider
     */
    private $productSelectDataProvider;

    /**
     * @var Generator
     */
    private $batchQueryGenerator;

    /**
     * @var ProductIndexFiller
     */
    private $productIndexFiller;

    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

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
     * @param Generator|null $batchQueryGenerator
     * @param ProductSelectDataProvider|null $productSelectDataProvider
     * @param ProductIndexFiller|null $productIndexFiller
     * @param ProcessManager|null $processManager
     * @param TableMaintainer|null $tableMaintainer
     * @param ScopeConfigInterface $scopeConfig
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
        ProcessManager $processManager = null,
        TableMaintainer $tableMaintainer = null,
        ScopeConfigInterface $scopeConfig = null
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->catalogConfig = $catalogConfig;
        $this->coreCache = $coreCache;
        $this->productMetadata = $metadataPool->getMetadata(ProductInterface::class);
        $this->categoryMetadata = $metadataPool->getMetadata(CategoryInterface::class);

        $this->scopeConfig = $scopeConfig
            ??ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->batchQueryGenerator = $batchQueryGenerator
            ?? ObjectManager::getInstance()->get(Generator::class);
        $this->productSelectDataProvider = $productSelectDataProvider
            ?? ObjectManager::getInstance()->get(ProductSelectDataProvider::class);
        $this->productIndexFiller = $productIndexFiller
            ?? ObjectManager::getInstance()->get(ProductIndexFiller::class);
        $this->processManager = $processManager
            ?? ObjectManager::getInstance()->get(ProcessManager::class);
        $this->tableMaintainer = $tableMaintainer ?? ObjectManager::getInstance()->get(TableMaintainer::class);
    }

    /**
     * Return validated table name
     *
     * @param string|string[] $table
     * @return string
     */
    protected function getTable($table)
    {
        return $this->resource->getTableName($table);
    }

    /**
     * Return category index table name
     *
     * @return string
     */
    protected function getIndexTable()
    {
        return $this->getTable(self::INDEX_TABLE);
    }

    /**
     * Return product index table
     *
     * @return string
     */
    protected function getProductIndexTable()
    {
        return $this->getIndexTable() . self::PRODUCT_SUFFIX;
    }

    /**
     * Return temporary category index table name
     *
     * If 'useIndexTempTable' flag is true:
     *  - return temporary index table name.
     *
     * If 'useIndexTempTable' flag is false:
     *  - return index table name.
     *
     * @return string
     */
    protected function getIndexTempTable()
    {
        return $this->useIndexTempTable ? $this->getTable(
            self::INDEX_TABLE . self::TMP_SUFFIX
        ) : $this->getIndexTable();
    }

    /**
     * Return temporary product index table name
     *
     * If 'useIndexTempTable' flag is true:
     *  - return temporary index table name.
     *
     * If 'useIndexTempTable' flag is false:
     *  - return index table name.
     *
     * @return string
     */
    protected function getProductIndexTempTable()
    {
        return $this->useIndexTempTable ? $this->getTable(
            self::INDEX_TABLE . self::PRODUCT_SUFFIX . self::TMP_SUFFIX
        ) : $this->getProductIndexTable();
    }

    /**
     * Retrieve list of customer group identifiers
     *
     * Return identifiers for all customer groups that exist in the system
     *
     * @return int[]
     */
    protected function getCustomerGroupIds()
    {
        if (!$this->customerGroupIds) {
            $this->customerGroupIds = $this->groupCollectionFactory->create()->getAllIds();
        }
        return $this->customerGroupIds;
    }

    /**
     * Retrieve list of website identifiers
     *
     * Return identifiers for all websites that exist in the system
     *
     * @return int[]
     */
    private function getWebsitesIds()
    {
        if (!$this->websitesIds) {
            $this->websitesIds = $this->websiteCollectionFactory->create()->addFieldToFilter(
                'website_id',
                ['neq' => 0]
            )->getAllIds();
        }
        return $this->websitesIds;
    }

    /**
     * Check whether select ranging is needed
     *
     * @return bool
     */
    abstract protected function isRangingNeeded();

    /**
     * Run reindexation
     *
     * @return void
     */
    protected function reindex()
    {
        $categoryList = $this->getCategoryList();
        foreach ($this->getCustomerGroupIds() as $customerGroupId) {
            $userFunctions[] = function () use ($customerGroupId, $categoryList) {
                $this->indexCategoryPermissions = [];

                $permissions = $this->getCategoryPermissions(array_keys($categoryList), $customerGroupId);
                foreach ($permissions as $permission) {
                    if (isset($categoryList[$permission['category_id']])) {
                        $this->prepareCategoryIndexPermissions($permission, $categoryList[$permission['category_id']]);
                    }
                }

                foreach ($categoryList as $categoryId => $path) {
                    $this->prepareInheritedCategoryIndexPermissions($categoryId, $path);
                }

                $this->populateCategoryIndex($customerGroupId);

                $this->populateProductIndex($customerGroupId);
                if ($this->tableMaintainer->getMode() === ModeSwitcher::DIMENSION_CUSTOMER_GROUP) {
                    $this->fixProductPermissionsWithGroups($customerGroupId);
                }
            };
        }

        $this->processManager->execute($userFunctions); /** @phpstan-ignore-line */
        if ($this->tableMaintainer->getMode() !== ModeSwitcher::DIMENSION_CUSTOMER_GROUP) {
            $this->fixProductPermissions();
        }
    }

    /**
     * Clean cache
     *
     * @return void
     */
    protected function cleanCache()
    {
        $this->coreCache->clean(
            [
                \Magento\Catalog\Model\Category::CACHE_TAG,
                \Magento\Framework\App\Cache\Type\Block::CACHE_TAG,
                \Magento\Framework\App\Cache\Type\Layout::CACHE_TAG
            ]
        );
    }

    /**
     * Retrieve category list
     *
     * Returns [entity_id, path] pairs.
     *
     * @return array
     */
    abstract protected function getCategoryList();

    /**
     * Retrieve permissions assigned to categories
     *
     * @param int[] $entityIds
     * @param int|null $customerGroupId
     * @return array
     */
    protected function getCategoryPermissions(array $entityIds, $customerGroupId = null)
    {
        $grants = [];
        foreach (array_keys($this->grantsInheritance) as $grant) {
            $grants[] = $this->connection->quoteInto(
                sprintf('permission.%s != ?', $grant),
                Permission::PERMISSION_PARENT
            );
        }

        $select = $this->connection->select()->from(
            ['permission' => $this->getTable('magento_catalogpermissions')],
            [
                'category_id',
                'website_id',
                'customer_group_id',
                'grant_catalog_category_view',
                'grant_catalog_product_price',
                'grant_checkout_items'
            ]
        )->where(
            '(' . implode(' OR ', $grants) . ')'
        )->where(
            'customer_group_id is NULL OR customer_group_id = ?',
            $customerGroupId
        )->order(
            ['category_id', 'website_id', 'customer_group_id']
        );

        if (!empty($entityIds)) {
            $select->where('permission.category_id IN (?)', $entityIds);
        }

        return $this->connection->fetchAll($select);
    }

    /**
     * Prepare list of permissions for certain category path
     *
     * @param array $permission
     * @param string $path
     * @return void
     */
    protected function prepareCategoryIndexPermissions(array $permission, $path)
    {
        $websiteIds = $permission['website_id'] === null ? $this->getWebsitesIds() : [$permission['website_id']];

        $customerGroupIds = $permission['customer_group_id'] === true
        ? $this->getCustomerGroupIds() : [
            $permission['customer_group_id']
        ];

        foreach ($websiteIds as $websiteId) {
            foreach ($customerGroupIds as $customerGroupId) {
                $permission['website_id'] = $websiteId;
                $permission['customer_group_id'] = $customerGroupId;
                $this->indexCategoryPermissions[$path][$websiteId . '_' . $customerGroupId] = $permission;
            }
        }
    }

    /**
     * Prepare grants for certain category path
     *
     * @param string $path
     * @return void
     */
    protected function prepareCategoryInheritance($path)
    {
        $parentPath = substr($path, 0, strrpos($path, '/'));
        foreach (array_keys($this->indexCategoryPermissions[$path]) as $uniqKey) {
            if (isset($this->indexCategoryPermissions[$parentPath][$uniqKey])) {
                foreach ($this->grantsInheritance as $grant => $inheritance) {
                    $value = $this->indexCategoryPermissions[$parentPath][$uniqKey][$grant];
                    if ($this->indexCategoryPermissions[$path][$uniqKey][$grant] == Permission::PERMISSION_PARENT) {
                        $this->indexCategoryPermissions[$path][$uniqKey][$grant] = $value;
                    } else {
                        if ($inheritance == self::GRANT_ALLOW) {
                            $value = max($this->indexCategoryPermissions[$path][$uniqKey][$grant], $value);
                        }
                        $value = min($this->indexCategoryPermissions[$path][$uniqKey][$grant], $value);
                        $this->indexCategoryPermissions[$path][$uniqKey][$grant] = $value;
                    }
                    if ($this->indexCategoryPermissions[$path][$uniqKey][$grant] == Permission::PERMISSION_PARENT) {
                        $this->indexCategoryPermissions[$path][$uniqKey][$grant] = null;
                    }
                }
            }
        }
    }

    /**
     * Check if permission was applied to all customer groups.
     *
     * @param string $path
     * @param string $parentUniqueKey
     * @return bool
     */
    private function isPermissionAppliedToAllCustomerGroups($path, $parentUniqueKey)
    {
        $parentUniqueKey = explode('_', $parentUniqueKey);
        $websiteId = $parentUniqueKey[0];

        foreach ($this->indexCategoryPermissions[$path] as $permission) {
            if ($permission['customer_group_id'] == null && $permission['website_id'] == $websiteId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inherit category permission from it's parent
     *
     * @param int $categoryId
     * @param string $path
     * @return void
     */
    protected function prepareInheritedCategoryIndexPermissions($categoryId, $path)
    {
        $parentPath = substr($path, 0, strrpos($path, '/'));

        if (isset($this->indexCategoryPermissions[$path])) {
            $this->prepareCategoryInheritance($path);
            if (isset($this->indexCategoryPermissions[$parentPath])) {
                foreach (array_keys($this->indexCategoryPermissions[$parentPath]) as $uniqKey) {
                    if (!isset($this->indexCategoryPermissions[$path][$uniqKey])
                        && !$this->isPermissionAppliedToAllCustomerGroups($path, $uniqKey)) {
                        // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
                        $this->indexCategoryPermissions[$path][$uniqKey] = array_merge(
                            $this->indexCategoryPermissions[$parentPath][$uniqKey],
                            ['category_id' => $categoryId]
                        );
                    }
                }
            }
        } elseif (isset($this->indexCategoryPermissions[$parentPath])) {
            foreach ($this->indexCategoryPermissions[$parentPath] as $uniqKey => $permission) {
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
                $this->indexCategoryPermissions[$path][$uniqKey] = array_merge(
                    $permission,
                    ['category_id' => $categoryId]
                );
            }
        }
    }

    /**
     * Prepare category permissions regarding category tree inheritance and specific customer groups overriding
     *
     * @return void
     * @SuppressWarnings(PHPMD)
     */
    protected function preparePermissionsInheritance()
    {
        foreach ($this->indexCategoryPermissions as $key => $permissions) {
            uasort($this->indexCategoryPermissions[$key], function ($element1, $element2) {
                return $element1['customer_group_id'] === null ? -1 : 1;
            });
        }
    }

    /**
     * Populate main index table with prepared permissions
     *
     * @param int $customerGroupId
     * @return void
     */
    protected function populateCategoryIndex($customerGroupId)
    {
        $this->preparePermissionsInheritance();
        foreach ($this->indexCategoryPermissions as $permissions) {
            $data = [];
            foreach ($permissions as $permission) {
                if ($permission['grant_catalog_category_view'] == Permission::PERMISSION_DENY) {
                    $permission['grant_catalog_product_price'] = Permission::PERMISSION_DENY;
                }
                if ($permission['grant_catalog_product_price'] == Permission::PERMISSION_DENY) {
                    $permission['grant_checkout_items'] = Permission::PERMISSION_DENY;
                }
                $data[] = [
                    (int)$permission['category_id'],
                    (int)$permission['website_id'],
                    (int)$customerGroupId,
                    (int)$permission['grant_catalog_category_view'],
                    (int)$permission['grant_catalog_product_price'],
                    (int)$permission['grant_checkout_items']
                ];
            }
            if (count($data) > 0) {
                if (str_ends_with($this->getIndexTempTable(), AbstractAction::REPLICA_SUFFIX)) {
                    $tableName = $this->tableMaintainer->
                    resolveReplicaTableNameCategory($customerGroupId, $this->useIndexTempTable);
                } else {
                    $tableName = $this->tableMaintainer->
                    resolveMainTableNameCategory($customerGroupId);
                }

                $this->connection->insertArray(
                    $tableName,
                    [
                        'category_id',
                        'website_id',
                        'customer_group_id',
                        'grant_catalog_category_view',
                        'grant_catalog_product_price',
                        'grant_checkout_items',
                    ],
                    $data,
                    AdapterInterface::REPLACE
                );
            }
        }
    }

    /**
     * Get permissions columns
     *
     * @return array
     */
    protected function getPermissionColumns()
    {
        $grantView = $this->getConfigGrantDbExpr(
            $this->config->getCatalogCategoryViewMode(),
            $this->config->getCatalogCategoryViewGroups()
        );
        $grantPrice = $this->getConfigGrantDbExpr(
            $this->config->getCatalogProductPriceMode(),
            $this->config->getCatalogProductPriceGroups()
        );
        $grantCheckout = $this->getConfigGrantDbExpr(
            $this->config->getCheckoutItemsMode(),
            $this->config->getCheckoutItemsGroups()
        );

        $connection = $this->connection;

        $exprCatalogCategoryView = $connection->getCheckSql(
            $connection->quoteInto('grant_catalog_category_view = ?', Permission::PERMISSION_PARENT),
            'NULL',
            'grant_catalog_category_view'
        );
        $exprCatalogProductPrice = $connection->getCheckSql(
            $connection->quoteInto('grant_catalog_product_price = ?', Permission::PERMISSION_PARENT),
            'NULL',
            'grant_catalog_product_price'
        );
        $exprCheckoutItems = $connection->getCheckSql(
            $connection->quoteInto('grant_checkout_items = ?', Permission::PERMISSION_PARENT),
            'NULL',
            'grant_checkout_items'
        );

        return [
            'grant_catalog_category_view' => new \Zend_Db_Expr('MAX(' . $connection->getCheckSql(
                $connection->quoteInto('? IS NULL', $exprCatalogCategoryView),
                $connection->quoteInto('?', $grantView),
                $connection->quoteInto('?', $exprCatalogCategoryView)
            ) . ')'),
            'grant_catalog_product_price' => new \Zend_Db_Expr('MAX(' . $connection->getCheckSql(
                $connection->quoteInto('? IS NULL', $exprCatalogProductPrice),
                $connection->quoteInto('?', $grantPrice),
                $connection->quoteInto('?', $exprCatalogProductPrice)
            ) . ')'),
            'grant_checkout_items' => new \Zend_Db_Expr('MAX(' . $connection->getCheckSql(
                $connection->quoteInto('? IS NULL', $exprCheckoutItems),
                $connection->quoteInto('?', $grantCheckout),
                $connection->quoteInto('?', $exprCheckoutItems)
            ) . ')')
        ];
    }

    /**
     * Create select for populating product index
     *
     * @param int $customerGroupId
     * @param int $storeId
     * @return \Magento\Framework\DB\Select
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createProductSelect($customerGroupId, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $select = $this->productSelectDataProvider->getSelect(
            $customerGroupId,
            $store,
            $this->tableMaintainer->resolveMainTableNameCategory($customerGroupId),
            $this->getProductList()
        );

        return $select;
    }

    /**
     * Return list of product IDs to reindex
     *
     * @return int[]
     */
    abstract protected function getProductList();

    /**
     * Populate product index
     *
     * @param int $customerGroupId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function populateProductIndex($customerGroupId)
    {
        if (str_ends_with($this->getIndexTempTable(), AbstractAction::REPLICA_SUFFIX)) {
            $tableName = $this->tableMaintainer->
            resolveReplicaTableNameCategory($customerGroupId, $this->useIndexTempTable);
        } else {
            $tableName = $this->tableMaintainer->
            resolveMainTableNameCategory($customerGroupId);
        }
        if (str_ends_with($this->getProductIndexTempTable(), AbstractAction::REPLICA_SUFFIX)) {
            $productTableName = $this->tableMaintainer->
            resolveReplicaTableNameProduct($customerGroupId, $this->useIndexTempTable);
        } else {
            $productTableName = $this->tableMaintainer->
            resolveMainTableNameProduct($customerGroupId);
        }
        foreach ($this->storeManager->getStores() as $store) {
            $this->productIndexFiller->populate(
                $store,
                $customerGroupId,
                $tableName,
                $productTableName,
                $this->getProductList()
            );
        }
    }

    /**
     * Fix product permissions after population
     *
     * @return void
     */
    protected function fixProductPermissions()
    {
        $deny = (int)Permission::PERMISSION_DENY;
        $data = [
            'grant_catalog_product_price' => $this->connection->getCheckSql(
                $this->connection->quoteInto('grant_catalog_category_view = ?', $deny),
                $deny,
                'grant_catalog_product_price'
            ),
            'grant_checkout_items' => $this->connection->getCheckSql(
                $this->connection->quoteInto(
                    'grant_catalog_category_view = ?',
                    $deny
                ) . ' OR ' . $this->connection->quoteInto(
                    'grant_catalog_product_price = ?',
                    $deny
                ),
                $deny,
                'grant_checkout_items'
            )
        ];

        $condition = $this->getProductList() ? ['product_id IN (?)' => $this->getProductList()] : '';
        $productIndexTempTable = $this->getProductIndexTempTable();
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            foreach ($this->getCustomerGroupIds() as $groupId) {
                $productIndexTableName = $productIndexTempTable . '_' . $groupId;
                $this->connection->update($productIndexTableName, $data, $condition);
            }
        } else {
            $this->connection->update($productIndexTempTable, $data, $condition);
        }
    }

    /**
     * Fix product permissions after population
     *
     * @param mixed $customerGroup
     * @return void
     */
    private function fixProductPermissionsWithGroups($customerGroup)
    {
        $deny = (int)Permission::PERMISSION_DENY;
        $data = [
            'grant_catalog_product_price' => $this->connection->getCheckSql(
                $this->connection->quoteInto('grant_catalog_category_view = ?', $deny),
                $deny,
                'grant_catalog_product_price'
            ),
            'grant_checkout_items' => $this->connection->getCheckSql(
                $this->connection->quoteInto(
                    'grant_catalog_category_view = ?',
                    $deny
                ) . ' OR ' . $this->connection->quoteInto(
                    'grant_catalog_product_price = ?',
                    $deny
                ),
                $deny,
                'grant_checkout_items'
            )
        ];

        $condition = $this->getProductList() ? ['product_id IN (?)' => $this->getProductList()] : '';

        $this->connection->update(
            $this->tableMaintainer->resolveMainTableNameProduct($customerGroup),
            $data,
            $condition
        );
    }

    /**
     * Generates CASE ... WHEN .... THEN expression for grant depends on config
     *
     * @param string $mode
     * @param string[] $groups
     * @return \Zend_Db_Expr
     */
    protected function getConfigGrantDbExpr($mode, $groups)
    {
        $result = new \Zend_Db_Expr('0');
        $conditions = [];
        $connection = $this->connection;

        foreach ($this->storeManager->getStores() as $store) {
            if ($mode == ConfigInterface::GRANT_CUSTOMER_GROUP) {
                foreach ($groups as $groupId) {
                    if (is_numeric($groupId)) {
                        // Case per customer group
                        $condition = $connection->quoteInto(
                            'store.store_id = ?',
                            $store->getId()
                        ) . ' AND ' . $connection->quoteInto(
                            'permission_index.customer_group_id = ?',
                            (int)$groupId
                        );
                        $conditions[$condition] = Permission::PERMISSION_ALLOW;
                    }
                }

                $condition = $connection->quoteInto('store.store_id = ?', $store->getId());
                $conditions[$condition] = Permission::PERMISSION_DENY;
            } else {
                $condition = $connection->quoteInto('store.store_id = ?', $store->getId());
                $conditions[$condition] = $mode !=
                    ConfigInterface::GRANT_NONE ? Permission::PERMISSION_ALLOW : Permission::PERMISSION_DENY;
            }
        }

        if (!empty($conditions)) {
            $result = new \Zend_Db_Expr('(' . $connection->getCaseSql('', $conditions) . ')');
        }

        return $result;
    }
}
