<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Model;

use Magento\Cms\Model\Block;
use Magento\Framework\Indexer\CacheContext;
use Magento\Staging\Model\StagingApplierInterface;

/**
 * Staging applier for cms blocks
 */
class BlockApplier implements StagingApplierInterface
{
    /**
     * @var CacheContext
     */
    private $cacheContext;

    /**
     * @param CacheContext $cacheContext
     */
    public function __construct(CacheContext $cacheContext)
    {
        $this->cacheContext = $cacheContext;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $entityIds)
    {
        if (!empty($entityIds)) {
            $this->cacheContext->registerEntities(Block::CACHE_TAG, $entityIds);
        }
    }
}
