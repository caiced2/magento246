<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Model\Indexer\Product\Flat\Table;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Builder
 */
class Builder
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
     * Builder constructor.
     *
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
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\Table\BuilderInterface $subject
     * @param \Magento\Framework\DB\Ddl\Table $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return \Magento\Framework\DB\Ddl\Table
     */
    public function afterGetTable(
        \Magento\Catalog\Model\Indexer\Product\Flat\Table\BuilderInterface $subject,
        \Magento\Framework\DB\Ddl\Table $result
    ) {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();
        $connectionName = $metadata->getEntityConnectionName();
        $connection = $this->resourceConnection->getConnectionByName($connectionName);
            $result->addColumn($linkField, \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER);
        $result->addIndex($connection->getIndexName($result->getName(), [$linkField]), [$linkField]);
        return $result;
    }
}
