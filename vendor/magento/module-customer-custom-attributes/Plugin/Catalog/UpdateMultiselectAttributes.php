<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Plugin\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;

/**
 * Updates multiselect attributes for product data
 */
class UpdateMultiselectAttributes
{
    /**
     * Update empty multiselect attributes for product data
     *
     * @param Helper $subject
     * @param ProductInterface $product
     * @return ProductInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterInitializeFromData(Helper $subject, ProductInterface $product): ProductInterface
    {
        $productData = $product->getData();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attrKey => $attribute) {
            if ($attribute->getFrontendInput() === 'multiselect') {
                if (array_key_exists($attrKey, $productData) && $productData[$attrKey] == null) {
                    $product->setData($attrKey, '');
                }
            }
        }
        return $product;
    }
}
