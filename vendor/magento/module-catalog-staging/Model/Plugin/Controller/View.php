<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Plugin\Controller;

use Magento\CatalogStaging\Model\Indexer\Category\Product\PreviewReindex;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Plugin to show a staging preview of the category
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class View
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PreviewReindex
     */
    private $previewReindex;

    /**
     * @param VersionManager $versionManager
     * @param StoreManagerInterface $storeManager
     * @param PreviewReindex $previewReindex
     */
    public function __construct(
        VersionManager $versionManager,
        StoreManagerInterface $storeManager,
        PreviewReindex $previewReindex
    ) {
        $this->versionManager = $versionManager;
        $this->storeManager = $storeManager;
        $this->previewReindex = $previewReindex;
    }

    /**
     * @param \Magento\Catalog\Controller\Category\View $subject
     * @return void
     */
    public function beforeExecute(\Magento\Catalog\Controller\Category\View $subject)
    {
        if (!$this->versionManager->isPreviewVersion()) {
            return;
        }
        $categoryId = (int) $subject->getRequest()->getParam('id');
        $storeId = (int) $this->storeManager->getStore()->getId();

        $this->previewReindex->reindex($categoryId, $storeId);
    }
}
