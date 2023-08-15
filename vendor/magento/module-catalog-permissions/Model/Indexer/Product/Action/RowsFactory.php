<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Product\Action;

/**
 * Factory class for \Magento\CatalogPermissions\Model\Indexer\Product\Action\Rows
 * @api
 * @since 100.0.2
 */
class RowsFactory extends \Magento\CatalogPermissions\Model\Indexer\Category\Action\RowsFactory
{
    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \Magento\CatalogPermissions\Model\Indexer\Product\Action\Rows::class
    ) {
        parent::__construct($objectManager, $instanceName);
    }
}
