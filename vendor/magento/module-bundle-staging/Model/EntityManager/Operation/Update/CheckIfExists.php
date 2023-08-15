<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BundleStaging\Model\EntityManager\Operation\Update;

/**
 * An operation that checks if an entity exists in a DB.
 */
class CheckIfExists implements \Magento\Framework\EntityManager\Operation\CheckIfExistsInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Framework\EntityManager\HydratorPool
     */
    private $hydratorPool;

    /**
     * @var \Magento\Framework\EntityManager\TypeResolver
     */
    private $typeResolver;

    /**
     * @param \Magento\Framework\EntityManager\TypeResolver $typeResolver
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param \Magento\Framework\EntityManager\HydratorPool $hydratorPool
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\EntityManager\TypeResolver $typeResolver,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Framework\EntityManager\HydratorPool $hydratorPool,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->typeResolver = $typeResolver;
        $this->metadataPool = $metadataPool;
        $this->hydratorPool = $hydratorPool;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($entity)
    {
        $entityType = $this->typeResolver->resolve($entity);

        $metadata = $this->metadataPool->getMetadata($entityType);
        $hydrator = $this->hydratorPool->getHydrator($entityType);

        $entityData = $hydrator->extract($entity);

        $connection = $this->resourceConnection->getConnectionByName(
            $metadata->getEntityConnectionName()
        );

        $indexList = $connection->getIndexList($metadata->getEntityTable());
        $primaryKeyName = $connection->getPrimaryKeyName($metadata->getEntityTable());

        $primaryKey  = $indexList[$primaryKeyName]['COLUMNS_LIST'];

        $select = $connection->select()->from($metadata->getEntityTable(), $primaryKey);

        foreach ($primaryKey as $column) {
            if (!isset($entityData[$column])) {
                return false;
            }

            $select->where($column . ' = ?', $entityData[$column]);
        }

        return (bool) $connection->fetchOne($select->limit(1));
    }
}
