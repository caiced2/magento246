<?php
/**
 * CMS menu hierarchy configuration model interface
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Model\Hierarchy;

use Magento\Framework\DataObject;

/**
 * Interface \Magento\VersionsCms\Model\Hierarchy\ConfigInterface
 *
 * @api
 */
interface ConfigInterface
{
    /**
     * Return available Context Menu layouts output
     *
     * @return array
     */
    public function getAllMenuLayouts();

    /**
     * Return Context Menu layout by its name
     *
     * @param string $layoutName
     * @return DataObject|bool
     */
    public function getContextMenuLayout($layoutName);
}
