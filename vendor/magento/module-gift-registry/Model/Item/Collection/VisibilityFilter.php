<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\Item\Collection;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Catalog\Model\Product\Visibility;
use Magento\GiftRegistry\Model\ResourceModel\Item\Collection;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Visibility filter for Gift Registry items Collection.
 */
class VisibilityFilter implements FilterInterface
{
    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * @var Visibility
     */
    private $productVisibility;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param TableMaintainer $tableMaintainer
     * @param Visibility $productVisibility
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        TableMaintainer $tableMaintainer,
        Visibility $productVisibility,
        StoreManagerInterface $storeManager
    ) {
        $this->tableMaintainer = $tableMaintainer;
        $this->productVisibility = $productVisibility;
        $this->storeManager = $storeManager;
    }

    /**
     * Filter items Collection by Product visibility.
     *
     * @param Collection $collection
     * @return void
     */
    public function execute(Collection $collection): void
    {
        $connection = $collection->getConnection();
        $store = $this->storeManager->getStore();
        $visibilityConditions = [
            'cat_index.product_id = main_table.product_id',
            $connection->quoteInto('cat_index.category_id = ?', $store->getRootCategoryId()),
            $connection->quoteInto('cat_index.visibility IN (?)', $this->productVisibility->getVisibleInSiteIds())
        ];

        $collection->getSelect()->join(
            ['cat_index' => $this->tableMaintainer->getMainTable((int) $store->getId())],
            join(' AND ', $visibilityConditions),
            []
        );
    }
}
