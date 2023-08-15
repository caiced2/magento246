<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin\Product;

use Magento\CatalogPermissions\Model\Indexer\Plugin\AbstractProduct;

class Action extends AbstractProduct
{
    /**
     * Reindex product permissions on product attribute mass change
     *
     * @param \Magento\Catalog\Model\Product\Action $subject
     * @param \Magento\Catalog\Model\Product\Action $result
     * @param int[] $productIds
     * @param int[] $attrData
     * @param int $storeId
     * @return \Magento\Catalog\Model\Product\Action
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateAttributes(
        \Magento\Catalog\Model\Product\Action $subject,
        \Magento\Catalog\Model\Product\Action $result,
        $productIds,
        $attrData,
        $storeId
    ) {
        $this->reindex($productIds);
        return $result;
    }

    /**
     * Reindex product permissions on product websites mass change
     *
     * @param \Magento\Catalog\Model\Product\Action $subject
     * @param null $result
     * @param int[] $productIds
     * @param int[] $websiteIds
     * @param string $type
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateWebsites(
        \Magento\Catalog\Model\Product\Action $subject,
        $result,
        $productIds,
        $websiteIds,
        $type
    ) {
        $this->reindex($productIds);
    }
}
