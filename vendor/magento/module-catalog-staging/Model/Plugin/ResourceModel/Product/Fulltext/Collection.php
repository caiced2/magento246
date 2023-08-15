<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Plugin\ResourceModel\Product\Fulltext;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\DB\Select;

/**
 * Class Collection
 */
class Collection
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param VersionManager $versionManager
     * @param MetadataPool $metadataPool
     */
    public function __construct(VersionManager $versionManager, MetadataPool $metadataPool)
    {
        $this->versionManager = $versionManager;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Search result index does not contain disabled products which can be enabled in future update version.
     *
     * @param \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $subject
     * @param mixed $printQuery
     * @param mixed $logQuery
     * @return void
     * @throws \Zend_Db_Select_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeLoad(
        \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $subject,
        $printQuery = null,
        $logQuery = null
    ) {
        $alias = ProductCollection::MAIN_TABLE_ALIAS . '_entity';
        $fromPart = $subject->getSelect()->getPart(Select::FROM);
        if ($subject->isEnabledFlat() && !isset($fromPart[$alias])) {
            $entityMetadata = $this->metadataPool->getMetadata(ProductInterface::class);
            $subject->getSelect()->join(
                [$alias => $entityMetadata->getEntityTable()],
                $alias . '.entity_id = ' . ProductCollection::MAIN_TABLE_ALIAS . '.entity_id',
                [$entityMetadata->getLinkField()]
            );
        }
    }
}
