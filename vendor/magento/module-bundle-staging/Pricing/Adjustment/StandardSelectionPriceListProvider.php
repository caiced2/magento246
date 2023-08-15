<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\BundleStaging\Pricing\Adjustment;

use Magento\Bundle\Pricing\Adjustment\Calculator;
use Magento\Bundle\Pricing\Adjustment\SelectionPriceListProviderInterface;
use Magento\Bundle\Model\Option;
use Magento\Catalog\Model\Product;
use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Pricing\Price\BundleOptionPrice;

/**
 * Provide standard implementation with iteration over all bundle selections
 */
class StandardSelectionPriceListProvider implements SelectionPriceListProviderInterface
{
    /**
     * @var Calculator
     */
    private $calculator;

    /**
     * @param Calculator $calculator
     */
    public function __construct(Calculator $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * @inheritdoc
     */
    public function getPriceList(Product $bundleProduct, $searchMin, $useRegularPrice)
    {
        // Flag shows - is it necessary to find minimal option amount in case if all options are not required
        $shouldFindMinOption = $this->isShouldFindMinOption($bundleProduct, $searchMin);
        $canSkipRequiredOptions = $searchMin && !$shouldFindMinOption;

        $currentPrice = false;
        $priceList = [];
        foreach ($this->getBundleOptions($bundleProduct) as $option) {
            if ($this->canSkipOption($option, $canSkipRequiredOptions)) {
                continue;
            }
            $selectionPriceList = $this->calculator->createSelectionPriceList(
                $option,
                $bundleProduct,
                $useRegularPrice
            );
            $selectionPriceList = $this->calculator->processOptions($option, $selectionPriceList, $searchMin);

            $lastSelectionPrice = end($selectionPriceList);
            $lastValue = $lastSelectionPrice->getAmount()->getValue() * $lastSelectionPrice->getQuantity();
            if ($shouldFindMinOption
                && (!$currentPrice ||
                    $lastValue < ($currentPrice->getAmount()->getValue() * $currentPrice->getQuantity()))
            ) {
                $currentPrice = end($selectionPriceList);
            } elseif (!$shouldFindMinOption) {
                $priceList[] = $selectionPriceList;
            }
        }
        $priceList = array_merge([], ...$priceList);

        return $shouldFindMinOption ? [$currentPrice] : $priceList;
    }

    /**
     * Flag shows - is it necessary to find minimal option amount in case if all options are not required
     *
     * @param Product $bundleProduct
     * @param bool $searchMin
     * @return bool
     */
    private function isShouldFindMinOption(Product $bundleProduct, $searchMin)
    {
        $shouldFindMinOption = false;
        if ($searchMin
            && $bundleProduct->getPriceType() == Price::PRICE_TYPE_DYNAMIC
            && !$this->hasRequiredOption($bundleProduct)
        ) {
            $shouldFindMinOption = true;
        }

        return $shouldFindMinOption;
    }

    /**
     * Check this option if it should be skipped
     *
     * @param Option $option
     * @param bool $canSkipRequiredOption
     * @return bool
     */
    private function canSkipOption($option, $canSkipRequiredOption)
    {
        $canSkip = true;
        if (!$option->getSelections() || ($canSkipRequiredOption && !$option->getRequired())) {
            return $canSkip;
        } else {
            foreach ($option->getSelections() as $selection) {
                if ($selection->isSalable()) {
                    $canSkip = false;
                    break;
                }
            }

            return $canSkip;
        }
    }

    /**
     * Check the bundle product for availability of required options
     *
     * @param Product $bundleProduct
     * @return bool
     */
    private function hasRequiredOption($bundleProduct)
    {
        $options = array_filter(
            $this->getBundleOptions($bundleProduct),
            function ($item) {
                return $item->getRequired();
            }
        );
        return !empty($options);
    }

    /**
     * Get bundle options
     *
     * @param Product $saleableItem
     * @return \Magento\Bundle\Model\ResourceModel\Option\Collection
     */
    private function getBundleOptions(Product $saleableItem)
    {
        /** @var BundleOptionPrice $bundlePrice */
        $bundlePrice = $saleableItem->getPriceInfo()->getPrice(BundleOptionPrice::PRICE_CODE);
        return $bundlePrice->getOptions();
    }
}
