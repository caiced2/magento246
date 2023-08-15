<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\CatalogPermissions\Model\Indexer\Product;

/**
 * Invalidate catalog permissions category and catalog permissions product index
 */
class InvalidateIndex implements UpdateIndexInterface
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * Constructor
     *
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function update(GroupInterface $group, $isGroupNew)
    {
        $this->indexerRegistry->get(Category::INDEXER_ID)->invalidate();
        $this->indexerRegistry->get(Product::INDEXER_ID)->invalidate();
    }
}
