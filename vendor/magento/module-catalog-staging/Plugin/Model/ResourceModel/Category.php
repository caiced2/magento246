<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Model\ResourceModel;

use Closure;
use Magento\Catalog\Model\ResourceModel\Category as ResourceModelCategory;
use Magento\Framework\DataObject;
use Magento\Staging\Model\VersionManager;

class Category
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
     * Check whether category can be deleted or not depends on preview version
     *
     * For preview version category can be always deleted
     *
     * @param ResourceModelCategory $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsForbiddenToDelete(ResourceModelCategory $subject, $result)
    {
        if ($this->versionManager->isPreviewVersion()) {
            return false;
        }
        return $result;
    }

    /**
     * @param ResourceModelCategory $subject
     * @param Closure $proceed
     * @param DataObject $object
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDeleteChildren(ResourceModelCategory $subject, Closure $proceed, DataObject $object)
    {
        if (!$this->versionManager->isPreviewVersion()) {
            return $proceed($object);
        }
    }
}
