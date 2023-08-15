<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\BundleStaging\Pricing\Adjustment;

use Magento\Bundle\Pricing\Adjustment\SelectionPriceListProviderInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Catalog\Model\Product;

/**
 * Provide selection price list depended on staging mode
 */
class SelectionPriceListProvider implements SelectionPriceListProviderInterface
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var SelectionPriceListProviderInterface
     */
    private $defaultSelectionPriceListProvider;

    /**
     * @var SelectionPriceListProviderInterface
     */
    private $standardSelectionPriceListProvider;

    /**
     * @param VersionManager $versionManager
     * @param SelectionPriceListProviderInterface $defaultSelectionPriceListProvider
     * @param SelectionPriceListProviderInterface $standardSelectionPriceListProvider
     */
    public function __construct(
        VersionManager $versionManager,
        SelectionPriceListProviderInterface $defaultSelectionPriceListProvider,
        SelectionPriceListProviderInterface $standardSelectionPriceListProvider
    ) {
        $this->defaultSelectionPriceListProvider = $defaultSelectionPriceListProvider;
        $this->standardSelectionPriceListProvider = $standardSelectionPriceListProvider;
        $this->versionManager = $versionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceList(Product $bundleProduct, $searchMin, $useRegularPrice)
    {
        if ($this->versionManager->isPreviewVersion()) {
            return $this->standardSelectionPriceListProvider
                ->getPriceList($bundleProduct, $searchMin, $useRegularPrice);
        } else {
            return $this->defaultSelectionPriceListProvider
                ->getPriceList($bundleProduct, $searchMin, $useRegularPrice);
        }
    }
}
