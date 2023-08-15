<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryStaging\Model\Plugin;

use Magento\CatalogInventory\Model\Configuration as CatalogInventoryConfiguration;
use Magento\Staging\Model\VersionManager;

/**
 * Plugin for CatalogInventory configuration
 */
class Configuration
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param VersionManager $versionManager
     */
    public function __construct(VersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * Wrapper for out of stock in staging preview.
     *
     * @param CatalogInventoryConfiguration $subject
     * @param \Closure $proceed
     * @param null|string|bool|int|\Magento\Store\Model\Store $store
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsShowOutOfStock(
        CatalogInventoryConfiguration $subject,
        \Closure $proceed,
        $store = null
    ): bool {
        if ($this->versionManager->isPreviewVersion()) {
            return true;
        }
        return $proceed($store);
    }

}
