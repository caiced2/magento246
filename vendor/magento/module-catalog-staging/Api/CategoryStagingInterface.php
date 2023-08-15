<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Api;

/**
 * Class CategoryStagingInterface
 * @api
 * @since 100.1.0
 */
interface CategoryStagingInterface
{
    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category
     * @param string $version
     * @param array $arguments
     * @return bool
     * @since 100.1.0
     */
    public function schedule(\Magento\Catalog\Api\Data\CategoryInterface $category, $version, $arguments = []);

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category
     * @param string $version
     * @return bool
     * @since 100.1.0
     */
    public function unschedule(\Magento\Catalog\Api\Data\CategoryInterface $category, $version);
}
