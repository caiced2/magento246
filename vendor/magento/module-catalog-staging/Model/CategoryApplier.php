<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Indexer\Category\Flat\State;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Staging\Model\StagingApplierInterface;

class CategoryApplier implements StagingApplierInterface
{
    /**
     * @var State
     */
    private $flatState;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var CacheContext
     */
    private $cacheContext;

    /**
     * @param State $flatState
     * @param IndexerRegistry $indexerRegistry
     * @param CacheContext $cacheContext
     */
    public function __construct(
        State $flatState,
        IndexerRegistry $indexerRegistry,
        CacheContext $cacheContext
    ) {
        $this->flatState = $flatState;
        $this->indexerRegistry = $indexerRegistry;
        $this->cacheContext = $cacheContext;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $entityIds)
    {
        if ($entityIds) {
            if ($this->flatState->isFlatEnabled()) {
                $this->indexerRegistry->get(State::INDEXER_ID)->reindexList($entityIds);
            }
            $this->cacheContext->registerEntities(Category::CACHE_TAG, $entityIds);
        }
    }
}
