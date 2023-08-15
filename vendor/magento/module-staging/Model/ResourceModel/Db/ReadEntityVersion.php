<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\ResourceModel\Db;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\VersionManager as VersionManager;
use Magento\Framework\DB\Select;
use Magento\Staging\Model\ResourceModel\Update;

/**
 * Class ReadEntityRow
 */
class ReadEntityVersion
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var Update
     */
    protected $updateResourceModel;

    /**
     * @param VersionManager $versionManager
     * @param MetadataPool $metadataPool
     * @param Update $updateResourceModel
     */
    public function __construct(
        VersionManager $versionManager,
        MetadataPool $metadataPool,
        Update $updateResourceModel
    ) {
        $this->versionManager = $versionManager;
        $this->metadataPool = $metadataPool;
        $this->updateResourceModel = $updateResourceModel;
    }

    /**
     * @param string $entityType
     * @param string $identifier
     * @return string
     * @throws \Exception
     */
    public function getCurrentVersionRowId($entityType, $identifier)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $identifierField = $metadata->getIdentifierField();
        return $metadata->getEntityConnection()->fetchOne(
            $metadata->getEntityConnection()
                ->select()
                ->from(
                    ['entity_table' => $metadata->getEntityTable()],
                    [$metadata->getLinkField()]
                )
                ->where($identifierField . ' = ?', $identifier)
                ->where('created_in = ?', $this->versionManager->getVersion()->getId())
                ->setPart('disable_staging_preview', true)
                ->forUpdate()
        );
    }

    /**
     * @param string $entityType
     * @param string $identifier
     * @return string
     * @throws \Exception
     */
    public function getActiveVersionRowId($entityType, $identifier)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $identifierField = $metadata->getIdentifierField();
        return $metadata->getEntityConnection()->fetchOne(
            $metadata->getEntityConnection()
                ->select()
                ->from(
                    ['entity_table' => $metadata->getEntityTable()],
                    [$metadata->getLinkField()]
                )
                ->where($identifierField . ' = ?', $identifier)
                ->forUpdate()
        );
    }

    /**
     * Retrieve ID of version that follows the given version
     *
     * @param string $entityType
     * @param int $versionId
     * @param int|null $entityId
     * @return int|string
     */
    public function getNextVersionId($entityType, $versionId, $entityId = null)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $select = $metadata->getEntityConnection()
            ->select()
            ->from(
                ['entity_table' => $metadata->getEntityTable()],
                ['created_in']
            )
            ->where('created_in > ?', $versionId)
            ->order('created_in ASC')
            ->setPart('disable_staging_preview', true)
            ->limit(1);
        if ($entityId !== null) {
            $select->where($metadata->getIdentifierField() . ' = ?', $entityId);
        }

        $nextVersionId = $metadata->getEntityConnection()->fetchOne($select);
        if (!$nextVersionId) {
            $nextVersionId = VersionManager::MAX_VERSION;
        }

        return $nextVersionId;
    }

    /**
     * Retrieve ID of permanent version that follows the given version
     *
     * @param string $entityType
     * @param int $versionId
     * @param int|null $entityId
     * @return int|string
     */
    public function getNextPermanentVersionId($entityType, $versionId, $entityId = null)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $connection = $metadata->getEntityConnection();
        $select = $connection
            ->select()
            ->from(
                ['entity_table' => $metadata->getEntityTable()],
                ['created_in']
            )
            ->join(
                ['update_table' => $this->updateResourceModel->getMainTable()],
                implode(
                    ' and ',
                    [
                        'entity_table.created_in = update_table.id',
                        'update_table.is_rollback is null',
                        'update_table.rollback_id is null'
                    ]
                ),
                []
            )
            ->where('created_in > ?', $versionId)
            ->order('created_in ASC')
            ->setPart('disable_staging_preview', true)
            ->limit(1);
        if ($entityId !== null) {
            $select->where($metadata->getIdentifierField() . ' = ?', $entityId);
        }

        $nextVersionId = $connection->fetchOne($select);
        if (!$nextVersionId) {
            $nextVersionId = VersionManager::MAX_VERSION;
        }

        return $nextVersionId;
    }

    /**
     * Retrieve ID of previous permanent version
     *
     * @param string $entityType
     * @param int $versionId
     * @param int|null $entityId
     * @return int|bool
     */
    public function getPreviousPermanentVersionId($entityType, $versionId, $entityId = null)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $connection = $metadata->getEntityConnection();
        $select = $connection
            ->select()
            ->from(
                ['entity_table' => $metadata->getEntityTable()],
                ['created_in']
            )
            ->join(
                ['update_table' => $this->updateResourceModel->getMainTable()],
                implode(
                    ' and ',
                    [
                        'entity_table.created_in = update_table.id',
                        'update_table.is_rollback is null',
                        'update_table.rollback_id is null'
                    ]
                ),
                []
            )
            ->where('created_in < ?', $versionId)
            ->order('created_in DESC')
            ->setPart('disable_staging_preview', true)
            ->limit(1);
        if ($entityId !== null) {
            $select->where($metadata->getIdentifierField() . ' = ?', $entityId);
        }

        $previousVersionId = $connection->fetchOne($select);
        if (!$previousVersionId) {
            $previousVersionId = 1;
        }
        return $previousVersionId;
    }

    /**
     * Retrieve ID of version that precedes the given version
     *
     * @param string $entityType
     * @param int $versionId
     * @param int|null $entityId
     * @return int|string
     */
    public function getPreviousVersionId($entityType, $versionId, $entityId = null)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $select = $metadata->getEntityConnection()
            ->select()
            ->from(
                ['entity_table' => $metadata->getEntityTable()],
                ['created_in']
            )
            ->where('created_in < ?', $versionId)
            ->order('created_in DESC')
            ->setPart('disable_staging_preview', true)
            ->limit(1);
        if ($entityId !== null) {
            $select->where($metadata->getIdentifierField() . ' = ?', $entityId);
        }

        $previousVersionId = $metadata->getEntityConnection()->fetchOne($select);
        if (!$previousVersionId) {
            $previousVersionId = 1;
        }

        return $previousVersionId;
    }

    /**
     * Retrieve IDs of versions that are marked as rollback and located within given interval
     *
     * @param string $entityType
     * @param int $startVersionId start of the interval
     * @param int $endVersionId end of the interval
     * @param int|null $entityId
     * @return array
     */
    public function getRollbackVersionIds($entityType, $startVersionId, $endVersionId, $entityId = null)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $connection = $metadata->getEntityConnection();
        $select = $connection
            ->select()
            ->from(
                ['entity_table' => $metadata->getEntityTable()],
                ['created_in']
            )
            ->join(
                ['update_table' => $this->updateResourceModel->getMainTable()],
                'entity_table.created_in = update_table.id and update_table.is_rollback = 1',
                []
            )
            ->where('created_in > ?', $startVersionId)
            ->where('created_in < ?', $endVersionId)
            ->order('created_in ASC')
            ->setPart('disable_staging_preview', true);
        if ($entityId !== null) {
            $select->where($metadata->getIdentifierField() . ' = ?', $entityId);
        }

        return $connection->fetchCol($select);
    }

    /**
     * @param string $entityType
     * @param string $identifier
     * @return string
     * @throws \Exception
     */
    public function getPreviousVersionRowId($entityType, $identifier)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        return $metadata->getEntityConnection()->fetchOne(
            $metadata->getEntityConnection()
                ->select()
                ->from(
                    ['entity_table' => $metadata->getEntityTable()],
                    ['row_id']
                )
                ->where($metadata->getIdentifierField() . ' = ?', $identifier)
                ->where('created_in < ?', $this->versionManager->getVersion()->getId())
                ->order('created_in DESC')
                ->setPart('disable_staging_preview', true)
                ->limit(1)
        );
    }

    /**
     * @param string $entityType
     * @param string $identifier
     * @return string
     */
    public function getCurrentRowIdForAffectedUpdate($entityType, $identifier)
    {
        $currentRowId = $this->getCurrentVersionRowId(
            $entityType,
            $identifier
        );
        if (!$currentRowId && !$this->versionManager->isPreviewVersion()) {
            $currentRowId = $this->getActiveVersionRowId(
                $entityType,
                $identifier
            );
        }
        return $currentRowId;
    }

    /**
     * Get entity row id by version id
     *
     * @param string $entityType
     * @param int $identifier
     * @param int $versionId
     * @return string
     */
    public function getVersionRowId($entityType, $identifier, $versionId)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $identifierField = $metadata->getIdentifierField();
        $select = $metadata->getEntityConnection()
            ->select()
            ->from(
                ['entity_table' => $metadata->getEntityTable()],
                [$metadata->getLinkField()]
            )
            ->where($identifierField . ' = ?', $identifier)
            ->where('created_in <= ?', $versionId)
            ->order('created_in DESC')
            ->limit(1)
            ->setPart('disable_staging_preview', true);

        return $metadata->getEntityConnection()->fetchOne($select);
    }
}
