<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Model\ResourceModel\Permission;

use Magento\Backend\Block\Widget\Tab;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\Flat\Collection as FlatCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogPermissions\Helper\Data as Helper;
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;
use Magento\CatalogPermissions\Model\Permission;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb as AbstractCollection;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\AdapterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;

/**
 * Catalog permissions index resource model.
 *
 * @api
 * @since 100.0.2
 */
class Index extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Catalog permissions data
     *
     * @var Helper
     */
    protected $helper;

    /**
     * Store manager instance
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param mixed $connectionName
     * @param mixed $tableMaintainer
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Helper $helper,
        StoreManagerInterface $storeManager,
        $connectionName = null,
        TableMaintainer $tableMaintainer = null
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->tableMaintainer = $tableMaintainer ?? ObjectManager::getInstance()->get(TableMaintainer::class);
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init('magento_catalogpermissions_index', 'category_id');
    }

    /**
     * Return product index table
     *
     * @return string
     */
    protected function getProductTable()
    {
        return $this->getMainTable() . \Magento\CatalogPermissions\Model\Indexer\AbstractAction::PRODUCT_SUFFIX;
    }

    /**
     * Retrieve permission index for category or categories with specified customer group and website id
     *
     * @param int|int[] $categoryId
     * @param int $customerGroupId
     * @param int $websiteId
     * @return array
     */
    public function getIndexForCategory($categoryId, $customerGroupId = null, $websiteId = null)
    {
        $connection = $this->getConnection();
        if (!is_array($categoryId)) {
            $categoryId = [$categoryId];
        }

        $select = $this->tableMaintainer->getInitialSelect(TableMaintainer::CATEGORY, $customerGroupId);
        $select->where('category_id IN (?)', $categoryId);

        if ($customerGroupId !== null) {
            $select->where('customer_group_id = ?', $customerGroupId);
        }
        if ($websiteId !== null) {
            $select->where('website_id = ?', $websiteId);
        }

        return ($customerGroupId !== null && $websiteId !== null)
            ? $connection->fetchAssoc($select)
            : $connection->fetchAll($select);
    }

    /**
     * Retrieve restricted category ids for customer group and website
     *
     * @param int $customerGroupId
     * @param int $websiteId
     * @return array
     */
    public function getRestrictedCategoryIds($customerGroupId, $websiteId)
    {
        $connection = $this->getConnection();

        $select = $this->tableMaintainer->getInitialSelect(TableMaintainer::CATEGORY, $customerGroupId)
            ->columns(['category_id'])
            ->where(
                'grant_catalog_category_view = :grant_catalog_category_view'
            );
        $bind = [];
        if ($customerGroupId !== null) {
            $select->where('customer_group_id = :customer_group_id');
            $bind[':customer_group_id'] = $customerGroupId;
        }
        if ($websiteId) {
            $select->where('website_id = :website_id');
            $bind[':website_id'] = $websiteId;
        }
        if (!$this->helper->isAllowedCategoryView()) {
            $bind[':grant_catalog_category_view'] = Permission::PERMISSION_ALLOW;
        } else {
            $bind[':grant_catalog_category_view'] = Permission::PERMISSION_DENY;
        }

        $restrictedCatIds = $connection->fetchCol($select, $bind);

        $select = $connection->select()->from($this->getTable('catalog_category_entity'), 'entity_id');

        if (!empty($restrictedCatIds) && !$this->helper->isAllowedCategoryView()) {
            $select->where('entity_id NOT IN(?)', $restrictedCatIds);
        } elseif (!empty($restrictedCatIds) && $this->helper->isAllowedCategoryView()) {
            $select->where('entity_id IN(?)', $restrictedCatIds);
        } elseif ($this->helper->isAllowedCategoryView()) {
            // category view allowed for all
            $select->where('1 = 0');
        }

        return $connection->fetchCol($select);
    }

    /**
     * Add index to category collection
     *
     * @param CategoryCollection|FlatCollection|AbstractCollection $collection
     * @param int $customerGroupId
     * @param int $websiteId
     * @return $this
     */
    public function addIndexToCategoryCollection(AbstractCollection $collection, $customerGroupId, $websiteId)
    {
        $connection = $this->getConnection();
        if ($collection instanceof FlatCollection) {
            $tableAlias = 'main_table';
        } else {
            $tableAlias = 'e';
        }

        $collection->getSelect()->joinLeft(
            ['perm' => $this->tableMaintainer->resolveMainTableNameCategory($customerGroupId)],
            'perm.category_id = ' . $tableAlias . '.entity_id' . ' AND ' . $connection->quoteInto(
                'perm.website_id = ?',
                $websiteId
            ) . ' AND ' . $connection->quoteInto(
                'perm.customer_group_id = ?',
                $customerGroupId
            ),
            []
        );

        if (!$this->helper->isAllowedCategoryView()) {
            $collection->getSelect()->where('perm.grant_catalog_category_view = ?', Permission::PERMISSION_ALLOW);
        } else {
            $collection->getSelect()->where(
                'perm.grant_catalog_category_view != ?' . ' OR perm.grant_catalog_category_view IS NULL',
                Permission::PERMISSION_DENY
            );
        }

        return $this;
    }

    /**
     * Add index select in product collection
     *
     * @param ProductCollection $collection
     * @param int $customerGroupId
     * @return $this
     */
    public function addIndexToProductCollection(ProductCollection $collection, $customerGroupId)
    {
        $connection = $this->getConnection();

        $fromPart = $collection->getSelect()->getPart(\Magento\Framework\DB\Select::FROM);

        $categoryId = isset(
            $collection->getLimitationFilters()['category_id']
        ) ? $collection->getLimitationFilters()['category_id'] : null;

        $conditions = [$connection->quoteInto('perm.customer_group_id = ?', $customerGroupId)];

        if (!$categoryId || $categoryId == $this->storeManager->getStore(
            $collection->getStoreId()
        )->getRootCategoryId()
        ) {
            $conditions[] = 'perm.product_id = e.entity_id';
            $conditions[] = $connection->quoteInto('perm.store_id = ?', $collection->getStoreId());
            $joinConditions = join(' AND ', $conditions);
            $tableName = $this->tableMaintainer->resolveMainTableNameProduct($customerGroupId);

            if (!isset($fromPart['perm'])) {
                $collection->getSelect()->joinLeft(
                    ['perm' => $tableName],
                    $joinConditions,
                    ['grant_catalog_category_view', 'grant_catalog_product_price', 'grant_checkout_items']
                );
            }
        } else {
            $conditions[] = 'perm.category_id = cat_index.category_id';
            $conditions[] = $connection->quoteInto(
                'perm.website_id = ?',
                $this->storeManager->getStore($collection->getStoreId())->getWebsiteId()
            );
            $joinConditions = join(' AND ', $conditions);
            $tableName = $this->tableMaintainer->resolveMainTableNameCategory($customerGroupId);

            if (!isset($fromPart['perm'])) {
                $collection->getSelect()->joinLeft(
                    ['perm' => $tableName],
                    $joinConditions,
                    ['grant_catalog_category_view', 'grant_catalog_product_price', 'grant_checkout_items']
                );
            }
        }

        if (isset($fromPart['perm'])) {
            $fromPart['perm']['tableName'] = $tableName;
            $fromPart['perm']['joinCondition'] = $joinConditions;
            $collection->getSelect()->setPart(\Magento\Framework\DB\Select::FROM, $fromPart);
            return $this;
        }

        if (!$this->helper->isAllowedCategoryView()) {
            $collection->getSelect()->where('perm.grant_catalog_category_view = ?', Permission::PERMISSION_ALLOW);
        } else {
            $collection->getSelect()->where(
                'perm.grant_catalog_category_view != ?' . ' OR perm.grant_catalog_category_view IS NULL',
                Permission::PERMISSION_DENY
            );
        }

        return $this;
    }

    /**
     * Add link limitations to product collection
     *
     * @param ProductCollection $collection
     * @return $this
     */
    protected function addLinkLimitation($collection)
    {
        if (method_exists($collection, 'getLinkModel') || $collection->getFlag('is_link_collection')) {
            $collection->getSelect()->where(
                'perm.grant_catalog_product_price != ?' . ' OR perm.grant_catalog_product_price IS NULL',
                Permission::PERMISSION_DENY
            )->where(
                'perm.grant_checkout_items != ?' . ' OR perm.grant_checkout_items IS NULL',
                Permission::PERMISSION_DENY
            );
        }
        return $this;
    }

    /**
     * Add permission index to product model
     *
     * @param Product $product
     * @param int $customerGroupId
     * @return $this
     */
    public function addIndexToProduct($product, $customerGroupId)
    {
        $connection = $this->getConnection();

        if ($product->getCategory()) {
            $select = $this->tableMaintainer->getInitialSelect(TableMaintainer::CATEGORY, $customerGroupId)
                ->columns(['grant_catalog_category_view', 'grant_catalog_product_price', 'grant_checkout_items'])
                ->where(
                    'category_id = ?',
                    $product->getCategory()->getId()
                )->where(
                    'customer_group_id = ?',
                    $customerGroupId
                )->where(
                    'website_id = ?',
                    $this->storeManager->getStore($product->getStoreId())->getWebsiteId()
                );
        } else {
            $select = $this->tableMaintainer->getInitialSelect(TableMaintainer::PRODUCT, $customerGroupId)
                ->columns(['grant_catalog_category_view', 'grant_catalog_product_price', 'grant_checkout_items'])
                ->where(
                    'product_id = ?',
                    $product->getId()
                )->where(
                    'customer_group_id = ?',
                    $customerGroupId
                )->where(
                    'store_id = ?',
                    $product->getStoreId()
                );
        }

        $permission = $connection->fetchRow($select);
        if ($permission) {
            $product->addData($permission);
        }

        return $this;
    }

    /**
     * Get permission index for products
     *
     * @param int|int[] $productId
     * @param int $customerGroupId
     * @param int $storeId
     * @return array
     */
    public function getIndexForProduct($productId, $customerGroupId, $storeId)
    {
        if (!is_array($productId)) {
            $productId = [$productId];
        }

        $connection = $this->getConnection();
        $select = $this->tableMaintainer->getInitialSelect(TableMaintainer::PRODUCT, $customerGroupId)
            ->columns(
                [
                    'product_id',
                    'grant_catalog_category_view',
                    'grant_catalog_product_price',
                    'grant_checkout_items',
                    'customer_group_id'
                ]
            )
            ->where(
                'product_id IN (?)',
                $productId
            )->where(
                'store_id = ?',
                $storeId
            );

        if (null !== $customerGroupId) {
            $select->where(
                'customer_group_id = ?',
                $customerGroupId
            );
        }

        return $customerGroupId === null ? $connection->fetchAll($select) : $connection->fetchAssoc($select);
    }
}
