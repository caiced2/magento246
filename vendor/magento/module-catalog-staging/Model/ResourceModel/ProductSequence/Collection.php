<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\ResourceModel\ProductSequence;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Sequence\SequenceRegistry;

class Collection
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var SequenceRegistry
     */
    private $sequenceRegistry;

    /**
     * Constructor
     *
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resource
     * @param SequenceRegistry $sequenceRegistry
     */
    public function __construct(
        MetadataPool $metadataPool,
        ResourceConnection $resource,
        SequenceRegistry $sequenceRegistry
    ) {
        $this->metadataPool = $metadataPool;
        $this->resource = $resource;
        $this->sequenceRegistry = $sequenceRegistry;
    }

    /**
     * Delete sequence
     *
     * @param array $ids
     * @return void
     * @throws \LogicException
     */
    public function deleteSequence(array $ids)
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $sequenceInfo = $this->sequenceRegistry->retrieve(ProductInterface::class);
        if (!isset($sequenceInfo['sequenceTable'])) {
            throw new \LogicException('Sequence table doesn\'t exist');
        }

        $metadata->getEntityConnection()->delete(
            $this->resource->getTableName($sequenceInfo['sequenceTable']),
            ['sequence_value IN (?)' => $ids]
        );
    }
}
