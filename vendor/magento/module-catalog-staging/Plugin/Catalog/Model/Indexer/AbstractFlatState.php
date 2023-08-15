<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Catalog\Model\Indexer;

/**
 * Plugin for Abstract Flat State.
 */
class AbstractFlatState
{
    /**
     * @var bool|null
     */
    private $isPreview;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @param \Magento\Staging\Model\VersionManager $versionManager
     */
    public function __construct(\Magento\Staging\Model\VersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * @param \Magento\Catalog\Model\Indexer\AbstractFlatState $subject
     * @param \Closure $proceed
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsAvailable(
        \Magento\Catalog\Model\Indexer\AbstractFlatState $subject,
        \Closure $proceed
    ) {
        if ($this->isPreview === null) {
            $this->isPreview = $this->versionManager->isPreviewVersion();
        }

        return $this->isPreview ? false : $proceed();
    }
}
