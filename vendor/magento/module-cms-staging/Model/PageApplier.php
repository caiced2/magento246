<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Model;

use Magento\Framework\Indexer\CacheContext;
use Magento\Staging\Model\StagingApplierInterface;

/**
 * Class PageApplier
 */
class PageApplier implements StagingApplierInterface
{
    /**
     * @var \Magento\Framework\Indexer\CacheContext
     */
    private $cacheContext;

    /**
     * PageStagingApplier constructor.
     * @param CacheContext $cacheContext
     */
    public function __construct(
        CacheContext $cacheContext
    ) {
        $this->cacheContext = $cacheContext;
    }

    /**
     * @param array $entityIds
     * @return void
     */
    public function execute(array $entityIds)
    {
        if (!empty($entityIds)) {
            $this->cacheContext->registerEntities(\Magento\Cms\Model\Page::CACHE_TAG, $entityIds);
        }
    }
}
