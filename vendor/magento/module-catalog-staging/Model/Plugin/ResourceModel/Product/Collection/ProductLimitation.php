<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Plugin\ResourceModel\Product\Collection;

use Magento\Staging\Model\VersionManager;

/**
 * Class ProductLimitation
 */
class ProductLimitation
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
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitation $subject
     * @param array $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsUsingPriceIndex(
        \Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitation $subject,
        $result
    ) {
        if ($this->versionManager->isPreviewVersion()) {
            $result = false;
        }
        return $result;
    }
}
