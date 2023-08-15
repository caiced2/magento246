<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Model\Indexer\Product\Action;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer as CategoryProductTableMaintainer;
use Magento\CatalogPermissions\App\Config;
use Magento\CatalogPermissions\Model\Permission;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Prepares select with products for indexer actions
 */
class ProductSelectDataProvider
{
    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CategoryProductTableMaintainer
     */
    private $categoryProductTableMaintainer;

    /**
     * @param ResourceConnection $resource
     * @param Config $config
     * @param CategoryProductTableMaintainer $categoryProductTableMaintainer
     */
    public function __construct(
        ResourceConnection $resource,
        Config $config,
        CategoryProductTableMaintainer $categoryProductTableMaintainer
    ) {
        $this->connection = $resource->getConnection();
        $this->config = $config;
        $this->categoryProductTableMaintainer = $categoryProductTableMaintainer;
    }

    /**
     * Build select with necessary permissions
     *
     * @param int $customerGroupId
     * @param StoreInterface $store
     * @param string $indexTableName
     * @param int[] $productList
     * @return Select
     */
    public function getSelect(
        int $customerGroupId,
        StoreInterface $store,
        string $indexTableName,
        array $productList
    ): Select {
        $select = $this->connection->select()->from(
            ['category_product_index' => $this->categoryProductTableMaintainer->getMainTable((int) $store->getId())],
            []
        )->joinInner(
            ['permission_index' => $indexTableName],
            'permission_index.category_id = category_product_index.category_id' .
            $this->connection->quoteInto(' AND permission_index.website_id = ?', $store->getWebsiteId(), 'INT') .
            $this->connection->quoteInto(' AND permission_index.customer_group_id = ?', $customerGroupId, 'INT'),
            []
        )->group(
            ['category_product_index.product_id']
        );

        if (!empty($productList)) {
            $select->where('category_product_index.product_id IN (?)', $productList, 'INT');
        }

        $select->columns(
            [
                'product_id' => 'category_product_index.product_id',
                'store_id' => new \Zend_Db_Expr($store->getId()),
                'customer_group_id' =>  new \Zend_Db_Expr($customerGroupId),
            ]
        );
        $permissionsColumns = $this->getPermissionColumns($store->getCode(), $customerGroupId);
        $select->columns($permissionsColumns);

        return $select;
    }

    /**
     * Get config value for specific customer group
     *
     * @param int $customerGroupId
     * @param int $mode
     * @param array $groups
     * @return int
     */
    private function getConfigGrantValue(int $customerGroupId, int $mode, array $groups): int
    {
        if (Config::GRANT_CUSTOMER_GROUP === $mode) {
            $result = in_array($customerGroupId, $groups)
                ? Permission::PERMISSION_ALLOW
                : Permission::PERMISSION_DENY;
        } else {
            $result = Config::GRANT_NONE === $mode
                ? Permission::PERMISSION_DENY
                : Permission::PERMISSION_ALLOW;
        }

        return $result;
    }

    /**
     * Get permissions columns
     *
     * @param string $storeCode
     * @param int $customerGroupId
     * @return array
     */
    private function getPermissionColumns(string $storeCode, int $customerGroupId): array
    {
        $fields = [];
        $fields['grant_catalog_category_view'] = $this->getConfigGrantValue(
            $customerGroupId,
            (int) $this->config->getCatalogCategoryViewMode($storeCode),
            $this->config->getCatalogCategoryViewGroups($storeCode)
        );
        $fields['grant_catalog_product_price'] = $this->getConfigGrantValue(
            $customerGroupId,
            (int) $this->config->getCatalogProductPriceMode($storeCode),
            $this->config->getCatalogProductPriceGroups($storeCode)
        );
        $fields['grant_checkout_items'] = $this->getConfigGrantValue(
            $customerGroupId,
            (int) $this->config->getCheckoutItemsMode($storeCode),
            $this->config->getCheckoutItemsGroups($storeCode)
        );

        $columns = [];
        foreach ($fields as $field => $configValue) {
            $parentExpression = $this->connection->getCheckSql(
                $field . ' = ' . Permission::PERMISSION_PARENT,
                'NULL',
                $field
            );
            $expression = $this->connection->getIfNullSql($parentExpression, $configValue);
            $columns[$field] = new \Zend_Db_Expr('MAX(' . $expression . ')');
        }

        return $columns;
    }
}
