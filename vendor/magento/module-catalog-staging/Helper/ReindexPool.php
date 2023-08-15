<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Helper;

/**
 * Class ReindexPool
 */
class ReindexPool
{
    /**
     * @var array
     */
    protected $reindexPool;

    /**
     * ReindexPool constructor.
     * @param array $reindexPool
     */
    public function __construct(array $reindexPool = [])
    {
        $this->reindexPool = $reindexPool;
    }

    /**
     * Run List reindex entities
     *
     * @param int[] $ids
     * @return void
     */
    public function reindexList($ids)
    {
        foreach ($this->reindexPool as $indexer) {
            /**
             * @var $indexer \Magento\Framework\Indexer\AbstractProcessor
             */
            $indexer->reindexList($ids, true);
        }
    }
}
