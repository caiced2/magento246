<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model\ResourceModel\Product\Price;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\App\ResourceConnection;

/**
 * Class for copying tier prices in rollback product.
 */
class TierPriceCopier
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection
    ) {
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Copy tier prices from old entity to new
     *
     * @param \Magento\Catalog\Model\Product $entity
     * @return bool
     * @throws \Exception
     */
    public function copy(\Magento\Catalog\Model\Product $entity): bool
    {
        $metadata = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
        $linkField = $metadata->getLinkField();
        $fromRowId = $entity->getOrigData($linkField);
        $toRowId = $entity->getData($linkField);

        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity_tier_price'), '')
            ->where($metadata->getLinkField() . ' = ?', $fromRowId);
        $insertColumns = [
            'all_groups' => 'all_groups',
            'customer_group_id' => 'customer_group_id',
            'qty' => 'qty',
            'value' => 'value',
            'website_id' => 'website_id',
            'percentage_value' => 'percentage_value',
            $metadata->getLinkField() => new \Zend_Db_Expr($toRowId)
        ];
        $select->columns($insertColumns);
        $query = $select->insertFromSelect(
            $this->resourceConnection->getTableName('catalog_product_entity_tier_price'),
            array_keys($insertColumns)
        );
        $connection->query($query);

        return true;
    }
}
