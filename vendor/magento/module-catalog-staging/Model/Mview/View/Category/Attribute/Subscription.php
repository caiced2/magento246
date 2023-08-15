<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Mview\View\Category\Attribute;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class Subscription implements statement building for staged category entity attribute subscription
 * @package Magento\CatalogStaging\Model\Mview\View\Category\Attribute
 * @deprecated 100.2.0
 */
class Subscription extends \Magento\CatalogStaging\Model\Mview\View\Attribute\Subscription
{
    /**
     * @param ResourceConnection $resource
     * @param \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory
     * @param \Magento\Framework\Mview\View\CollectionInterface $viewCollection
     * @param \Magento\Framework\Mview\ViewInterface $view
     * @param string $tableName
     * @param string $columnName
     * @param MetadataPool $metadataPool
     * @param string|null $entityInterface
     * @param array $ignoredUpdateColumns
     */
    public function __construct(
        ResourceConnection $resource,
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory,
        \Magento\Framework\Mview\View\CollectionInterface $viewCollection,
        \Magento\Framework\Mview\ViewInterface $view,
        $tableName,
        $columnName,
        MetadataPool $metadataPool,
        $entityInterface = CategoryInterface::class,
        $ignoredUpdateColumns = []
    ) {
        parent::__construct(
            $resource,
            $triggerFactory,
            $viewCollection,
            $view,
            $tableName,
            $columnName,
            $metadataPool,
            $entityInterface,
            $ignoredUpdateColumns
        );
    }
}
