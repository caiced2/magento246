<?php
declare(strict_types=1);

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer;

use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

class TableMaintainer
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var GroupCollectionFactory
     */
    private $groupCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $customerGroupsArr = [];

    public const CATEGORY = 'category';

    public const PRODUCT = 'product';

    /**
     * @param ResourceConnection $resource
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResourceConnection $resource,
        GroupCollectionFactory $groupCollectionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resource;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get connection
     *
     * @return AdapterInterface
     */
    private function getConnection()
    {
        if (!isset($this->connection)) {
            $this->connection = $this->resource->getConnection();
        }
        return $this->connection;
    }

    /**
     * Return validated table name
     *
     * @param string|string[] $table
     * @return string
     */
    private function getTable($table)
    {
        return $this->resource->getTableName($table);
    }

    /**
     *  Resolve Category Indexer Suffix
     *
     * @param mixed $customerGroupId
     * @return string
     */
    private function resolveSuffixForCategoryIndexer($customerGroupId = null)
    {
        return $customerGroupId === null ? $this->getTable(AbstractAction::INDEX_TABLE)
            : $this->getTable(AbstractAction::INDEX_TABLE) . '_'
            . $customerGroupId;
    }

    /**
     * Resolve Product Indexer Suffix
     *
     * @param mixed $customerGroupId
     * @return string
     */
    private function resolveSuffixForProductIndexer($customerGroupId = null)
    {
        return $customerGroupId === null ?
            $this->getTable(
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
            ) :
            $this->getTable(
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
            ) . '_' . $customerGroupId;
    }

    /**
     * Retrieve Category Tables for Customer Groups
     *
     * @return array
     */
    public function getAllCategoryTablesForCustomerGroups()
    {
        $tables = [];

        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            foreach ($this->getCustomerGroupIds() as $customerGroupId) {
                $tables[] = $this->resolveSuffixForCategoryIndexer($customerGroupId);
            }
        } else {
            $tables[] = $this->resolveSuffixForCategoryIndexer();
        }

        return $tables;
    }

    /**
     * Retrieve Product Tables for Customer Groups
     *
     * @return array
     */
    public function getAllProductsTablesForCustomerGroups()
    {
        $tables = [];

        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            foreach ($this->getCustomerGroupIds() as $customerGroupId) {
                $tables[] = $this->resolveSuffixForProductIndexer($customerGroupId);
            }
        } else {
            $tables[] = $this->resolveSuffixForProductIndexer();
        }

        return $tables;
    }

    /**
     * Get all Customer group Ids
     *
     * @return array
     */
    private function getCustomerGroupIds()
    {
        if (empty($this->customerGroupsArr)) {
            $this->customerGroupsArr = $this->groupCollectionFactory->create()->getAllIds();
        }

        return $this->customerGroupsArr;
    }

    /**
     * Generate Tables for current Mode
     *
     * @param string $currentMode
     * @return bool
     * @throws \Zend_Db_Exception
     */
    public function createTablesForCurrentMode(string $currentMode)
    {
        if ($currentMode == ModeSwitcher::DIMENSION_CUSTOMER_GROUP) {
            return $this->createTablesForCustomersGroupMode();
        }

        return $this->createTablesForNoneMode();
    }

    /**
     * Generate Tables for Customer Group Mode
     *
     * @return bool
     * @throws \Zend_Db_Exception
     */
    private function createTablesForCustomersGroupMode()
    {
        foreach ($this->getCustomerGroupIds() as $customerGroupId) {
            $this->createTable(
                $this->resolveSuffixForCategoryIndexer(),
                $this->resolveSuffixForCategoryIndexer($customerGroupId)
            );
            $this->createTable(
                $this->resolveSuffixForProductIndexer(),
                $this->resolveSuffixForProductIndexer($customerGroupId)
            );
            $this->createTable(
                $this->resolveSuffixForCategoryIndexer(),
                $this->resolveSuffixForCategoryIndexer($customerGroupId) . AbstractAction::REPLICA_SUFFIX
            );
            $this->createTable(
                $this->resolveSuffixForProductIndexer(),
                $this->resolveSuffixForProductIndexer($customerGroupId) . AbstractAction::REPLICA_SUFFIX
            );
        }

        return true;
    }

    /**
     * Generate Tables for None Mode
     *
     * @return bool
     * @throws \Zend_Db_Exception
     */
    private function createTablesForNoneMode()
    {
        foreach ($this->getCustomerGroupIds() as $customerGroupId) {
            $this->createTable(
                $this->resolveSuffixForCategoryIndexer($customerGroupId),
                $this->resolveSuffixForCategoryIndexer()
            );
            $this->createTable(
                $this->resolveSuffixForProductIndexer($customerGroupId),
                $this->resolveSuffixForProductIndexer()
            );
            $this->createTable(
                $this->resolveSuffixForCategoryIndexer(),
                $this->resolveSuffixForCategoryIndexer() . AbstractAction::REPLICA_SUFFIX
            );
            $this->createTable(
                $this->resolveSuffixForProductIndexer(),
                $this->resolveSuffixForProductIndexer() . AbstractAction::REPLICA_SUFFIX
            );

            break;
        }

        return true;
    }

    /**
     * Create New Table
     *
     * @param string $createFromTableName
     * @param string $newTableName
     * @throws \Zend_Db_Exception
     */
    private function createTable($createFromTableName, $newTableName)
    {
        if (!$this->getConnection()->isTableExists($newTableName)) {
            $this->getConnection()->createTable(
                $this->getConnection()->createTableByDdl($createFromTableName, $newTableName)
            );
        }
    }

    /**
     * Drop redundant data
     *
     * @param string $currentMode
     * @return bool
     */
    public function dropOldData($currentMode)
    {
        if ($currentMode !== ModeSwitcher::DIMENSION_CUSTOMER_GROUP) {
            return $this->dropTablesForDimesnions();
        }

        return $this->dropMainTables();
    }

    /**
     * Drop redundant tables for Dimensions
     *
     * @return bool
     */
    private function dropTablesForDimesnions()
    {
        foreach ($this->groupCollectionFactory->create()->getAllIds() as $customerGroupId) {
            $this->getConnection()->dropTable($this->resolveSuffixForCategoryIndexer($customerGroupId));
            $this->getConnection()->dropTable($this->resolveSuffixForProductIndexer($customerGroupId));
            $this->getConnection()->dropTable(
                $this->resolveSuffixForCategoryIndexer($customerGroupId) . AbstractAction::REPLICA_SUFFIX
            );
            $this->getConnection()->dropTable(
                $this->resolveSuffixForProductIndexer($customerGroupId) . AbstractAction::REPLICA_SUFFIX
            );
        }

        return true;
    }

    /**
     * Drop redundant Main tables
     *
     * @return bool
     */
    private function dropMainTables()
    {
        $this->getConnection()->dropTable($this->resolveSuffixForCategoryIndexer());
        $this->getConnection()->dropTable($this->resolveSuffixForProductIndexer());
        $this->getConnection()->dropTable(
            $this->resolveSuffixForCategoryIndexer() . AbstractAction::REPLICA_SUFFIX
        );
        $this->getConnection()->dropTable(
            $this->resolveSuffixForProductIndexer() . AbstractAction::REPLICA_SUFFIX
        );

        return true;
    }

    /**
     * Get Main Category Table name
     *
     * @param mixed $customerGroupId
     * @return string
     */
    public function resolveMainTableNameCategory($customerGroupId)
    {
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            return $this->resolveSuffixForCategoryIndexer($customerGroupId);
        }

        return $this->resolveSuffixForCategoryIndexer();
    }

    /**
     * Get Category Permission Dimensions Mode
     *
     * @return mixed
     */
    public function getMode()
    {
        return  $this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE);
    }

    /**
     * Get Replica Category Table name
     *
     * @param string $customerGroupId
     * @param bool $useTmpFlag
     * @return string
     */
    public function resolveReplicaTableNameCategory($customerGroupId, $useTmpFlag = false)
    {
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            return $this->resolveSuffixForCategoryIndexer($customerGroupId) . AbstractAction::REPLICA_SUFFIX;
        }

        return $useTmpFlag ? $this->resolveSuffixForCategoryIndexer() . AbstractAction::TMP_SUFFIX
            : $this->resolveSuffixForCategoryIndexer() . AbstractAction::REPLICA_SUFFIX;
    }

    /**
     * Get Main Product Table Name
     *
     * @param mixed $customerGroupId
     * @return string
     */
    public function resolveMainTableNameProduct($customerGroupId)
    {
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            return $this->resolveSuffixForProductIndexer($customerGroupId);
        }

        return $this->resolveSuffixForProductIndexer();
    }

    /**
     * Get Replica Product table name
     *
     * @param string $customerGroupId
     * @param bool $useTmpFlag
     * @return string
     */
    public function resolveReplicaTableNameProduct($customerGroupId, $useTmpFlag = false)
    {
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            return $this->resolveSuffixForProductIndexer($customerGroupId) . AbstractAction::REPLICA_SUFFIX;
        }

        return $useTmpFlag ? $this->resolveSuffixForProductIndexer() . AbstractAction::TMP_SUFFIX
            : $this->resolveSuffixForProductIndexer() . AbstractAction::REPLICA_SUFFIX;
    }

    /**
     * Clear Temp Index table
     *
     * @return bool
     */
    public function clearIndexTempTable()
    {
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            foreach ($this->getCustomerGroupIds() as $customerGroupId) {
                $this->getConnection()->truncateTable($this->resolveSuffixForCategoryIndexer($customerGroupId));
                $this->getConnection()->truncateTable($this->resolveSuffixForProductIndexer($customerGroupId));
            }
        } else {
            $this->getConnection()->truncateTable($this->resolveSuffixForCategoryIndexer());
            $this->getConnection()->truncateTable($this->resolveSuffixForProductIndexer());
        }

        return true;
    }

    /**
     * Get Init Select
     *
     * @param string $catOrProduct
     * @param mixed $customerGroupId
     * @return Select
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    public function getInitialSelect($catOrProduct, $customerGroupId = null)
    {
        $select = $this->getConnection()->select();
        $table = $catOrProduct === self::CATEGORY
            ? $this->resolveSuffixForCategoryIndexer()
            : $this->resolveSuffixForProductIndexer();

        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) !==
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            return $select->from($table);
        }

        if ($customerGroupId === null) {
            $selectArr = [];

            foreach ($this->groupCollectionFactory->create()->getAllIds() as $customerGroupCollId) {
                $newSelect = $this->getConnection()->select();
                $selectArr[] = $newSelect->from($table . '_' . $customerGroupCollId);
            }
            $select->union($selectArr, \Magento\Framework\DB\Select::SQL_UNION_ALL);

            return $this->getConnection()->select()->from($select);
        }

        return $select->from($table . '_' . $customerGroupId);
    }
}
