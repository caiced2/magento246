<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\Catalog\Model\Product;
use Magento\CatalogPermissions\Helper\Data;

class ApplyPermissionsOnProduct
{
    /**
     * Catalog permission helper
     *
     * @var Data
     */
    protected $_catalogPermData;

    /**
     * Constructor
     *
     * @param Data $catalogPermData
     */
    public function __construct(
        Data $catalogPermData
    ) {
        $this->_catalogPermData = $catalogPermData;
    }

    /**
     * Apply category related permissions on product
     *
     * @param Product $product
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute($product)
    {
        if ($product->getData('grant_catalog_category_view') == -2
            || $product->getData('grant_catalog_category_view') != -1
            && !$this->_catalogPermData->isAllowedCategoryView()
        ) {
            $product->setIsHidden(true);
        }

        if ($product->getData('grant_catalog_product_price') == -2
            || $product->getData('grant_catalog_product_price') != -1
            && !$this->_catalogPermData->isAllowedProductPrice()
        ) {
            $product->setCanShowPrice(false);
            $product->setDisableAddToCart(true);
        }

        if ($product->getData('grant_checkout_items') == -2
            || $product->getData('grant_checkout_items') != -1
            && !$this->_catalogPermData->isAllowedCheckoutItems()
        ) {
            $product->setDisableAddToCart(true);
        }

        return $this;
    }
}
