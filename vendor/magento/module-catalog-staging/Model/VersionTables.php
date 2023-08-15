<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model;

/**
 * Class VersionTables stores information about staged tables.
 *
 * @package Magento\CatalogStaging\Model
 * @deprecated 100.2.0
 */
class VersionTables extends \Magento\Framework\DataObject
{
    /**
     * @return mixed
     * @deprecated 100.2.0
     */
    public function getVersionTables()
    {
        return (array)$this->getData('version_tables');
    }
}
