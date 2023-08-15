<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCmsPageCache\Plugin\VersionsCms\Hierarchy;

use Magento\VersionsCms\Model\Hierarchy\Node as HierarchyNode;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\PageCache\Model\Cache\Type as PageCache;

/**
 * Hierarchy node plugin to reset page cache after nodes save
 */
class Node
{
    /**
     * @var TypeListInterface
     */
    private $appCache;

    /**
     * @param TypeListInterface $appCache
     */
    public function __construct(
        TypeListInterface $appCache
    ) {
        $this->appCache = $appCache;
    }

    /**
     * Reset page cache after nodes save
     *
     * @param HierarchyNode $subject
     * @param HierarchyNode $result
     * @return HierarchyNode
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCollectTree(HierarchyNode $subject, HierarchyNode $result) : HierarchyNode
    {
        $this->appCache->invalidate(PageCache::TYPE_IDENTIFIER);

        return $result;
    }
}
