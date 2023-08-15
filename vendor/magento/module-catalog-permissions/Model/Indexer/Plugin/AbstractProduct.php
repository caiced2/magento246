<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

abstract class AbstractProduct
{
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface
     */
    protected $config;

    /**
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\CatalogPermissions\App\ConfigInterface $config
     */
    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogPermissions\App\ConfigInterface $config
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->config = $config;
    }

    /**
     * Reindex by product if indexer is enabled and not scheduled
     *
     * @param int[] $productIds
     * @return void
     */
    protected function reindex(array $productIds)
    {
        if ($this->config->isEnabled()) {
            $indexer = $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID);
            if (!$indexer->isScheduled()) {
                $indexer->reindexList($productIds);
            }
        }
    }
}
