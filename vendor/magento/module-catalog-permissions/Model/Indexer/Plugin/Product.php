<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

class Product extends AbstractProduct
{
    /**
     * Reindex product permissions on product save
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    public function afterSave(\Magento\Catalog\Model\Product $product)
    {
        $this->reindex([$product->getId()]);
        return $product;
    }

    /**
     * Reindex product permissions on product delete
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    public function afterDelete(\Magento\Catalog\Model\Product $product)
    {
        $this->reindex([$product->getId()]);
        return $product;
    }
}
