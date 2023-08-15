<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Model\ResourceModel\Category;

use Closure;
use Magento\Catalog\Model\Category;
use Magento\Staging\Model\VersionManager;

class AggregateCount
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @param VersionManager $versionManager ,
     */
    public function __construct(VersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Category\AggregateCount $subject
     * @param Closure $proceed
     * @param Category $category
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundProcessDelete(
        \Magento\Catalog\Model\ResourceModel\Category\AggregateCount $subject,
        Closure $proceed,
        Category $category
    ) {
        if (!$this->versionManager->isPreviewVersion()) {
            $proceed($category);
        }
    }
}
