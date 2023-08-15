<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\Cache;

use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class Index extends TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    const TARGET_RULE = 'target_rule';

    /**
     * Cache tag used to distinguish the cache type from all other cache
     */
    const CACHE_TAG = 'TARGET_RULE';

    /**
     * @param \Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool
     */
    public function __construct(\Magento\Framework\App\Cache\Type\FrontendPool $cacheFrontendPool)
    {
        parent::__construct($cacheFrontendPool->get(self::TARGET_RULE), self::CACHE_TAG);
    }
}
