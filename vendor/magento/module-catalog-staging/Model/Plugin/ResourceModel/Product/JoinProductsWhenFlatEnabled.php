<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Plugin\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\DB\Select;

class JoinProductsWhenFlatEnabled
{

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param MetadataPool $metadataPool
     */
    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param ProductCollection $subject
     * @param null $printQuery
     * @param null $logQuery
     * @return void
     * @throws \Exception
     * @throws \Zend_Db_Select_Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeLoad(
        ProductCollection $subject,
        $printQuery = null,
        $logQuery = null
    ) {
        $alias = 'product_entity';
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
