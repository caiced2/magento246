<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogUrlRewriteStaging\Plugin\Catalog\Model\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\CatalogUrlRewrite\Model\Category\ChildrenCategoriesProvider;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlPersistInterface;

class Remove extends \Magento\CatalogUrlRewrite\Model\Category\Plugin\Category\Remove
{
    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @param UrlPersistInterface $urlPersist
     * @param ProductUrlRewriteGenerator $productUrlRewriteGenerator
     * @param ChildrenCategoriesProvider $childrenCategoriesProvider
     * @param \Magento\Staging\Model\VersionManager $versionManager
     */
    public function __construct(
        UrlPersistInterface $urlPersist,
        ProductUrlRewriteGenerator $productUrlRewriteGenerator,
        ChildrenCategoriesProvider $childrenCategoriesProvider,
        \Magento\Staging\Model\VersionManager $versionManager
    ) {
        parent::__construct($urlPersist, $productUrlRewriteGenerator, $childrenCategoriesProvider);
        $this->versionManager = $versionManager;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Category $subject
     * @param \Closure $proceed
     * @param CategoryInterface $category
     * @return \Magento\Catalog\Model\ResourceModel\Category
     */
    public function aroundDelete(
        \Magento\Catalog\Model\ResourceModel\Category $subject,
        \Closure $proceed,
        CategoryInterface $category
    ) {
        if (!$this->isUpdateRemoval()) {
            return parent::aroundDelete($subject, $proceed, $category);
        }
        return $proceed($category);
    }

    /**
     * Check whether is in update editing mode
     * @return bool
     */
    protected function isUpdateRemoval()
    {
        return $this->versionManager->isPreviewVersion();
    }
}
