<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Api;

/**
 * Interface PageStagingInterface
 * @api
 * @since 100.1.0
 */

interface PageStagingInterface
{
    /**
     * @param \Magento\Cms\Api\Data\PageInterface $page
     * @param string $version
     * @param array $arguments
     * @return bool
     * @since 100.1.0
     */
    public function schedule(\Magento\Cms\Api\Data\PageInterface $page, $version, $arguments = []);

    /**
     * @param \Magento\Cms\Api\Data\PageInterface $page
     * @param string $version
     * @return bool
     * @since 100.1.0
     */
    public function unschedule(\Magento\Cms\Api\Data\PageInterface $page, $version);
}
