<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class StagingList
 */
class StagingList
{
    /**
     * @var string[]
     */
    protected $tables;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var string[]
     */
    protected $entities;

    /**
     * @param MetadataPool $metadataPool
     * @param string[] $entities
     */
    public function __construct(
        MetadataPool $metadataPool,
        array $entities = []
    ) {
        $this->metadataPool = $metadataPool;
        $this->entities = $entities;
    }

    /**
     * Returns list of tables that store staging entities
     *
     * @return string[]
     */
    public function getEntitiesTables()
    {
        if ($this->tables === null) {
            $this->tables = [];
            foreach ($this->getEntityTypes() as $entityType => $entity) {
                $metadata = $this->metadataPool->getMetadata($entity);
                $this->tables[$entityType] = $metadata->getEntityTable();
            }
        }
        return $this->tables;
    }

    /**
     * Returns list of types staging entities
     *
     * @return string[]
     */
    public function getEntityTypes()
    {
        return $this->entities;
    }
}
