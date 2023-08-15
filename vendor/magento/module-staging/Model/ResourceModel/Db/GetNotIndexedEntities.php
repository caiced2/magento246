<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\ResourceModel\Db;

use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class GetNotIndexedEntities
 */
class GetNotIndexedEntities
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @param MetadataPool $metadataPool
     */
    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * Fetch not indexed entities
     *
     * @param string $entityType
     * @param string|int $lastProcessedVersionId
     * @param string|int $currentVersionId
     * @return array
     * @throws \Exception
     */
    public function execute($entityType, $lastProcessedVersionId, $currentVersionId)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        return array_unique($metadata->getEntityConnection()->fetchCol(
            $metadata->getEntityConnection()->select()->reset()->from(
                ['entity_table' => $metadata->getEntityTable()],
                [$metadata->getIdentifierField()]
            )->where(
                'created_in > ?',
                $lastProcessedVersionId
            )->where(
                'created_in <= ?',
                $currentVersionId
            )->setPart(
                'disable_staging_preview',
                true
            )
        ));
    }
}
