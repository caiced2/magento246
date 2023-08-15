<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerStaging\Plugin;

use Magento\Banner\Block\Ajax\Data;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\VersionManager;

/**
 * Plugin to disable banner caching on staging preview
 */
class DisableBannerCache
{
    /**
     * @var VersionManager
     */
    private VersionManager $versionManager;

    /**
     * @param VersionManager $versionManager
     */
    public function __construct(
        VersionManager $versionManager
    ) {
        $this->versionManager = $versionManager;
    }

    /**
     * Disables banner caching on staging preview
     *
     * @param Data $subject
     * @param int $cacheTtl
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCacheTtl(Data $subject, int $cacheTtl): int
    {
        if ($this->versionManager->isPreviewVersion()) {
            $cacheTtl = 0;
        }
        return $cacheTtl;
    }
}
