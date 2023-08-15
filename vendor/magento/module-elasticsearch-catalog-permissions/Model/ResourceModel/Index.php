<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ElasticsearchCatalogPermissions\Model\ResourceModel;

/**
 * Class Index
 */
class Index
{
    /**
     * @var \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index
     */
    private $categoryPermissionsIndex;

    /**
     * @param \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index $categoryPermissionsIndex
     */
    public function __construct(
        \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index $categoryPermissionsIndex
    ) {
        $this->categoryPermissionsIndex = $categoryPermissionsIndex;
    }

    /**
     * Prepare system index data for products.
     *
     * @param array $productIds
     * @param int $storeId
     * @return array
     */
    public function getProductPermissionsIndexData(array $productIds, int $storeId)
    {
        $data = $this->categoryPermissionsIndex->getIndexForProduct($productIds, null, $storeId);

        $result = [];
        foreach ($data as $row) {
            $result[$row['product_id']][$row['customer_group_id']] = $row['grant_catalog_category_view'];
        }

        return $result;
    }
}
