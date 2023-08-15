<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\ResourceModel\Db;

use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class DeleteObsoleteEntities
 */
class DeleteObsoleteEntities
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
     * @param string $entityType
     * @param string|int $currentVersionId
     * @param string|int $maximumVersionsInDB
     * @return void
     * @throws \Exception
     * @throws \Zend_Db_Select_Exception
     */
    public function execute($entityType, $currentVersionId, $maximumVersionsInDB)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $entities = $metadata->getEntityConnection()->fetchAll(
            $metadata->getEntityConnection()->select()->reset()->from(
                ['table_name' => $metadata->getEntityTable()],
                [$metadata->getIdentifierField(), $metadata->getLinkField()]
            )->where(
                sprintf('updated_in <= %s', $currentVersionId)
            )->setPart('disable_staging_preview', true),
            [],
            \Zend_Db::FETCH_GROUP | \Zend_Db::FETCH_COLUMN
        );
        $rowIds = [];
        $currentVersionCount = 1;
        foreach ($entities as $versions) {
            $versionCountWithCurrent = count($versions) + $currentVersionCount;
            if ($versionCountWithCurrent > $maximumVersionsInDB) {
                rsort($versions, SORT_NUMERIC);
                $rowIds = array_merge(
                    $rowIds,
                    $this->getRedundantVersions($versions, $maximumVersionsInDB - $currentVersionCount)
                );
            }
        }
        if ($rowIds) {
            $metadata->getEntityConnection()->delete(
                $metadata->getEntityTable(),
                ['row_id IN (?)' => $rowIds]
            );
        }
    }

    /**
     * @param array $versions
     * @param int $maximumOldVersionsCount
     * @return array
     */
    private function getRedundantVersions(array $versions, $maximumOldVersionsCount)
    {
        return array_splice($versions, $maximumOldVersionsCount);
    }
}
