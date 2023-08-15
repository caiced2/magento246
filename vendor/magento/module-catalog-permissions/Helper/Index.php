<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Helper;

use Magento\Framework\App\ResourceConnection;

/**
 * Class Index
 *
 * @api
 * @since 100.0.2
 */
class Index
{
    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
    }

    /**
     * Returns paths for given categories
     *
     * @param array $categoryIds
     * @return array
     */
    protected function getCategoriesPaths(array $categoryIds)
    {
        $select = $this->connection->select()->from(
            $this->getTable('catalog_category_entity'),
            ['path']
        )->where(
            'entity_id IN (?)',
            $categoryIds
        );
        return $this->connection->fetchCol($select);
    }

    /**
     * Returns array of child categories ids
     *
     * @param array $categoryIds
     * @return array
     */
    public function getChildCategories(array $categoryIds)
    {
        $categoriesPathList = $this->getCategoriesPaths($categoryIds);

        $select = $this->connection->select()->from(
            $this->getTable('catalog_category_entity'),
            ['entity_id']
        )->order(
            'level ASC'
        );

        foreach ($categoriesPathList as $path) {
            $select->orWhere('path LIKE ?', $path . '/%');
        }

        return $this->connection->fetchCol($select);
    }

    /**
     * Retrieve category list
     * Returns [entity_id, path] pairs.
     *
     * @param array $categoryIds
     * @return array
     */
    public function getCategoryList(array $categoryIds)
    {
        $categoriesPathList = $this->getCategoriesPaths($categoryIds);

        $select = $this->connection->select()->from(
            $this->getTable('catalog_category_entity'),
            ['entity_id', 'path']
        )->order(
            'level ASC'
        );

        $calculatedEntityIds = [];
        foreach ($categoriesPathList as $path) {
            $select->where('path LIKE ?', $path . '/%');
            $calculatedEntityIds = array_merge($calculatedEntityIds, explode('/', $path));
        }

        $select->orWhere('entity_id IN (?)', array_unique($calculatedEntityIds));

        return $this->connection->fetchPairs($select);
    }

    /**
     * Return list of product IDs to reindex
     *
     * @param array $categoryIds
     * @return \int[]
     */
    public function getProductList(array $categoryIds)
    {
        $select = $this->connection->select()->from(
            $this->getTable('catalog_category_product'),
            'product_id'
        )->distinct(
            true
        )->where(
            'category_id IN (?)',
            $categoryIds
        );

        return $this->connection->fetchCol($select);
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
}
