<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\ProductType;

use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\HandlerInterface;
use Magento\Catalog\Model\Product;

class Bundle implements HandlerInterface
{
    /**
     * Handle selection data of bundle products
     *
     * @param Product $product
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function handle(Product $product)
    {
        if ($product->getTypeId() != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            return;
        }

        $bundleSelectionsData = $product->getBundleSelectionsData();

        if (!is_array($bundleSelectionsData)) {
            return;
        }

        // Retrieve original selections data

        /** @var \Magento\Bundle\Model\Product\Type $type */
        $type = $product->getTypeInstance();
        $type->setStoreFilter($product->getStoreId(), $product);

        $optionCollection = $type->getOptionsCollection($product);
        $selectionCollection = $type->getSelectionsCollection($type->getOptionsIds($product), $product);

        $origBundleOptions = $optionCollection->appendSelections($selectionCollection);
        $origBundleOptionsAssoc = [];

        foreach ($origBundleOptions as $origBundleOption) {
            /** @var \Magento\Bundle\Model\Option $origBundleOption */
            $optionId = $origBundleOption->getOptionId();
            $origBundleOptionsAssoc[$optionId] = [];
            if ($origBundleOption->getSelections()) {
                foreach ($origBundleOption->getSelections() as $selection) {
                    /** @var \Magento\Bundle\Model\Selection $selection */
                    $selectionProductId = $selection->getProductId();
                    $origBundleOptionsAssoc[$optionId][$selectionProductId] = [
                        'selection_price_type' => $selection->getSelectionPriceType(),
                        'selection_price_value' => $selection->getSelectionPriceValue(),
                    ];
                }
            }
        }

        // Keep previous price and price type for selections
        foreach ($bundleSelectionsData as &$bundleOptionSelections) {
            foreach ($bundleOptionSelections as &$bundleOptionSelection) {
                if (!isset($bundleOptionSelection['option_id']) || !isset($bundleOptionSelection['product_id'])) {
                    continue;
                }
                $optionId = $bundleOptionSelection['option_id'];
                $selectionProductId = $bundleOptionSelection['product_id'];
                $isDeleted = $bundleOptionSelection['delete'];
                if (isset($origBundleOptionsAssoc[$optionId][$selectionProductId]) && !$isDeleted) {
                    $bundleOptionSelection['selection_price_type'] =
                        $origBundleOptionsAssoc[$optionId][$selectionProductId]['selection_price_type'];
                    $bundleOptionSelection['selection_price_value'] =
                        $origBundleOptionsAssoc[$optionId][$selectionProductId]['selection_price_value'];
                } else {
                    // Set zero price for new bundle selections and options
                    $bundleOptionSelection['selection_price_type'] = 0;
                    $bundleOptionSelection['selection_price_value'] = 0;
                }
            }
        }
        $product->setData('bundle_selections_data', $bundleSelectionsData);
    }
}
