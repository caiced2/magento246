<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Staging\Model\StagingList;

/**
 * Staging update resource
 */
class Update extends AbstractDb
{
    /**
     * The property value used to check the model for save. If it is not new it requires additional preparation to
     * save the model properly
     *
     * @var bool
     */
    protected $_useIsObjectNew = true;

    /**
     * The primary key auto increment flag. In this model the auto increment is not used for the primary key.
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * @var string
     */
    protected $_eventPrefix = 'staging_update_resource';

    /**
     * @var array
     */
    private $versionCache;

    /**
     * @var StagingList
     */
    private $stagingList;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param Context $context
     * @param Snapshot $entitySnapshot
     * @param RelationComposite $entityRelationComposite
     * @param string|null $connectionName
     * @param StagingList|null $stagingList
     * @param MetadataPool|null $metadataPool
     */
    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        $connectionName = null,
        StagingList $stagingList = null,
        MetadataPool $metadataPool = null
    ) {
        $this->stagingList = $stagingList ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(StagingList::class);
        $this->metadataPool = $metadataPool ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(MetadataPool::class);
        $this->resourceConnection = $context->getResources();
        parent::__construct($context, $entitySnapshot, $entityRelationComposite, $connectionName);
    }

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('staging_update', 'id');
    }

    /**
     * Retrieve max version id for requested datetime
     *
     * @param int $timestamp
     * @return string
     */
    public function getMaxIdByTime($timestamp)
    {
        if (!isset($this->versionCache[$timestamp])) {
            $date = new \DateTime();
            $date->setTimestamp($timestamp);
            $select = $this->getConnection()->select()
                ->from($this->getMainTable())
                ->where('start_time <= ?', $date->format('Y-m-d H:i:s'))
                ->where('moved_to IS NULL')
                ->order(['id ' . \Magento\Framework\DB\Select::SQL_DESC])
                ->limit(1);
            $this->versionCache[$timestamp] = $this->getConnection()->fetchOne($select);
        }
        return $this->versionCache[$timestamp];
    }

    /**
     * Check for update entities associated with rollback_id.
     *
     * Receives optional parameter $ignoredUpdates with array of update ids which will be filtered in request.
     *
     * @param int $rollbackId
     * @param array $ignoredUpdates
     * @return bool
     */
    public function isRollbackAssignedToUpdates(int $rollbackId, array $ignoredUpdates = []): bool
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->where('rollback_id = ?', $rollbackId)
            ->where('id NOT IN (?)', $ignoredUpdates)
            ->limit(1);

        return (bool)$this->getConnection()->fetchOne($select);
    }

    /**
     * @inheritdoc
     */
    protected function processAfterSaves(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getOldId() && $object->getOldId() != $object->getId()) {
            if (!$object->getIsRollback()) {
                $bind = ['moved_to' => $object->getId()];
                // In case update become permanent - remove rollback_id from old record.
                if (!$object->getRollbackId()) {
                    $bind['rollback_id'] = null;
                }
                $this->getConnection()->update(
                    $this->getMainTable(),
                    $bind,
                    ['id = ?' => $object->getOldId()]
                );
            } elseif (!$object->getOldOriginId()) {
                foreach ($this->stagingList->getEntityTypes() as $entityType) {
                    $metadata = $this->metadataPool->getMetadata($entityType);
                    $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
                    $connection->update(
                        $metadata->getEntityTable(),
                        ['updated_in' => $object->getId()],
                        ['updated_in = ?' => $object->getOldId()]
                    );
                    $connection->update(
                        $metadata->getEntityTable(),
                        ['created_in' => $object->getId()],
                        ['created_in = ?' => $object->getOldId()]
                    );
                }
            }
        }

        parent::processAfterSaves($object);
    }

    /**
     * Get update to which provided one is moved.
     *
     * @param int $movedTo
     * @return int|null
     */
    public function getPreviousUpdateId(int $movedTo): ?int
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable(), ['id'])
            ->where('moved_to = ?', $movedTo)
            ->limit(1);
        $updateId = $this->getConnection()->fetchOne($select);

        return $updateId ? (int) $updateId : null;
    }
}
