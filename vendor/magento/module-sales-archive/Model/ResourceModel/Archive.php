<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Model\ResourceModel;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Model\ResourceModel\Attribute;
use Magento\Sales\Model\ResourceModel\EntityAbstract;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\SalesArchive\Model\Config;
use Magento\SalesArchive\Model\ResourceModel\Archive\TableMapper;
use Magento\SalesSequence\Model\Manager;
use Zend_Db_Expr;

/**
 * Archive resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Archive extends EntityAbstract
{
    /**
     * Archive entities tables association
     *
     * @var $_tables array
     * @deprecated 100.3.1
     */
    protected $_tables = [
        \Magento\SalesArchive\Model\ArchivalList::ORDER => [
            'sales_order_grid',
            'magento_sales_order_grid_archive',
        ],
        \Magento\SalesArchive\Model\ArchivalList::INVOICE => [
            'sales_invoice_grid',
            'magento_sales_invoice_grid_archive',
        ],
        \Magento\SalesArchive\Model\ArchivalList::SHIPMENT => [
            'sales_shipment_grid',
            'magento_sales_shipment_grid_archive',
        ],
        \Magento\SalesArchive\Model\ArchivalList::CREDITMEMO => [
            'sales_creditmemo_grid',
            'magento_sales_creditmemo_grid_archive',
        ],
    ];

    /**
     * Sales archive config
     *
     * @var Config
     */
    protected $_salesArchiveConfig;

    /**
     * Sales archival model list
     *
     * @var ArchivalList
     */
    protected $_archivalList;

    /** @var DateTime */
    protected $dateTime;

    /** @var TableMapper */
    private $tableMapper;

    /**
     * @param Context $context
     * @param Snapshot $entitySnapshot
     * @param RelationComposite $entityRelationComposite
     * @param Attribute $attribute
     * @param Manager $sequenceManager
     * @param Config $salesArchiveConfig
     * @param ArchivalList $archivalList
     * @param DateTime $dateTime
     * @param string $connectionName
     * @param TableMapper|null $tableMapper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        Attribute $attribute,
        Manager $sequenceManager,
        Config $salesArchiveConfig,
        ArchivalList $archivalList,
        DateTime $dateTime,
        $connectionName = null,
        TableMapper $tableMapper = null
    ) {
        $this->_salesArchiveConfig = $salesArchiveConfig;
        $this->_archivalList = $archivalList;
        $this->dateTime = $dateTime;
        $this->tableMapper = $tableMapper ?: ObjectManager::getInstance()->get(TableMapper::class);
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $attribute,
            $sequenceManager,
            $connectionName
        );
    }

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
    }

    /**
     * Check archive entity existence
     *
     * @param string $archiveEntity
     * @return bool
     */
    public function isArchiveEntityExists($archiveEntity)
    {
        return $this->tableMapper->isArchiveEntityExists($archiveEntity);
    }

    /**
     * Get archive entity table
     *
     * @param string $archiveEntity
     * @return false|string
     */
    public function getArchiveEntityTable($archiveEntity)
    {
        return $this->tableMapper->getArchiveEntityTable($archiveEntity);
    }

    /**
     * Retrieve archive entity source table
     *
     * @param string $archiveEntity
     * @return false|string
     */
    public function getArchiveEntitySourceTable($archiveEntity)
    {
        return $this->tableMapper->getArchiveEntitySourceTable($archiveEntity);
    }

    /**
     * Checks if order already in archive
     *
     * @param int $id order id
     * @return bool
     */
    public function isOrderInArchive($id)
    {
        $ids = $this->getIdsInArchive(ArchivalList::ORDER, [$id]);
        return !empty($ids);
    }

    /**
     * Retrieve entity ids in archive
     *
     * @param string $archiveEntity
     * @param array|int $ids
     * @return array
     */
    public function getIdsInArchive($archiveEntity, $ids)
    {
        if (!$this->isArchiveEntityExists($archiveEntity) || empty($ids)) {
            return [];
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $select = $this->getConnection()->select()->from(
            $this->getArchiveEntityTable($archiveEntity),
            'entity_id'
        )->where(
            'entity_id IN(?)',
            $ids
        );

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Retrieve order ids for archive
     *
     * @param array $orderIds
     * @param bool $useAge
     * @return array
     */
    public function getOrderIdsForArchive($orderIds = [], $useAge = false)
    {
        $statuses = $this->_salesArchiveConfig->getArchiveOrderStatuses();
        $archiveAge = $useAge ? $this->_salesArchiveConfig->getArchiveAge() : 0;

        if (empty($statuses)) {
            return [];
        }

        $select = $this->_getOrderIdsForArchiveSelect($statuses, $archiveAge);
        if (!empty($orderIds)) {
            $select->where('entity_id IN(?)', $orderIds);
        }
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Retrieve order ids in archive select
     *
     * @param array $statuses
     * @param int $archiveAge
     * @return \Magento\Framework\DB\Select
     */
    protected function _getOrderIdsForArchiveSelect($statuses, $archiveAge)
    {
        $connection = $this->getConnection();
        $table = $this->getArchiveEntitySourceTable(ArchivalList::ORDER);
        $select = $connection->select()->from($table, 'entity_id')->where('status IN(?)', $statuses);

        if ($archiveAge) {
            // Check archive age
            $archivePeriodExpr = $connection->getDateSubSql(
                $connection->quote($this->dateTime->formatDate(true)),
                (int)$archiveAge,
                AdapterInterface::INTERVAL_DAY
            );
            $select->where($archivePeriodExpr . ' >= updated_at');
        }

        return $select;
    }

    /**
     * Retrieve order ids for archive subselect expression
     *
     * @return Zend_Db_Expr
     */
    public function getOrderIdsForArchiveExpression()
    {
        $statuses = $this->_salesArchiveConfig->getArchiveOrderStatuses();
        $archiveAge = $this->_salesArchiveConfig->getArchiveAge();

        if (empty($statuses)) {
            $statuses = [0];
        }
        $select = $this->_getOrderIdsForArchiveSelect($statuses, $archiveAge);
        return new Zend_Db_Expr($select);
    }

    /**
     * Move records to from regular grid tables to archive
     *
     * @param string $archiveEntity
     * @param string $conditionField
     * @param array $conditionValue
     * @return $this
     */
    public function moveToArchive($archiveEntity, $conditionField, $conditionValue)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return $this;
        }
        $connection = $this->getConnection();
        $sourceTable = $this->getArchiveEntitySourceTable($archiveEntity);
        $targetTable = $this->getArchiveEntityTable($archiveEntity);

        $insertFields = array_intersect(
            array_keys($connection->describeTable($targetTable)),
            array_keys($connection->describeTable($sourceTable))
        );

        $fieldCondition = $connection->quoteIdentifier($conditionField) . ' IN(?)';
        $select = $connection->select()->from($sourceTable, $insertFields)->where($fieldCondition, $conditionValue);

        $connection->query($select->insertFromSelect($targetTable, $insertFields, true));
        return $this;
    }

    /**
     * Remove regords from source grid table
     *
     * @param string $archiveEntity
     * @param string $conditionField
     * @param array $conditionValue
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeFromGrid($archiveEntity, $conditionField, $conditionValue)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return $this;
        }
        $connectionMock = $this->getConnection();
        $sourceTable = $this->getArchiveEntitySourceTable($archiveEntity);
        $targetTable = $this->getArchiveEntityTable($archiveEntity);
        $sourceResource = $this->_archivalList->getResource($archiveEntity);
        if ($conditionValue instanceof Zend_Db_Expr) {
            $select = $connectionMock->select();
            // Remove order grid records moved to archive
            $select->from($targetTable, $sourceResource->getIdFieldName());
            $condition = $connectionMock->quoteInto(
                $sourceResource->getIdFieldName() . ' IN(?)',
                new Zend_Db_Expr($select)
            );
        } else {
            $fieldCondition = $connectionMock->quoteIdentifier($conditionField) . ' IN(?)';
            $condition = $connectionMock->quoteInto($fieldCondition, $conditionValue);
        }

        $connectionMock->delete($sourceTable, $condition);
        return $this;
    }

    /**
     * Remove records from archive
     *
     * @param string $archiveEntity
     * @param string $conditionField
     * @param array|null $conditionValue
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeFromArchive($archiveEntity, $conditionField = '', $conditionValue = null)
    {
        if (!$this->isArchiveEntityExists($archiveEntity)) {
            return $this;
        }
        $connection = $this->getConnection();
        $sourceTable = $this->getArchiveEntityTable($archiveEntity);
        $targetTable = $this->getArchiveEntitySourceTable($archiveEntity);
        $sourceResource = $this->_archivalList->getResource($archiveEntity);

        $insertFields = array_intersect(
            array_keys($connection->describeTable($targetTable)),
            array_keys($connection->describeTable($sourceTable))
        );

        $selectFields = $insertFields;

        $updatedAtIndex = array_search('updated_at', $selectFields);
        if ($updatedAtIndex !== false) {
            unset($selectFields[$updatedAtIndex]);
            unset($insertFields[$updatedAtIndex]);
            $insertFields[] = 'updated_at';
            $selectFields['updated_at'] = new Zend_Db_Expr('current_timestamp()');
        }

        $select = $connection->select()->from($sourceTable, $selectFields);

        if (!empty($conditionField)) {
            $select->where($connection->quoteIdentifier($conditionField) . ' IN(?)', $conditionValue);
        }

        $connection->query($select->insertFromSelect($targetTable, $insertFields, true));
        if ($conditionValue instanceof Zend_Db_Expr) {
            $select->reset()->from($targetTable, $sourceResource->getIdFieldName());
            // Remove order grid records from archive
            $condition = $connection->quoteInto(
                $sourceResource->getIdFieldName() . ' IN(?)',
                new Zend_Db_Expr($select)
            );
        } elseif (!empty($conditionField)) {
            $condition = $connection->quoteInto(
                $connection->quoteIdentifier($conditionField) . ' IN(?)',
                $conditionValue
            );
        } else {
            $condition = '';
        }

        $connection->delete($sourceTable, $condition);
        return $this;
    }

    /**
     * Removes orders from archive and restore in orders grid tables, returns restored order ids
     *
     * @param array $orderIds
     * @throws Exception
     * @return array
     */
    public function removeOrdersFromArchiveById($orderIds)
    {
        $this->beginTransaction();
        try {
            foreach ($this->_archivalList->getEntityNames() as $entity) {
                $conditionalField = 'order_id';
                if ($entity === ArchivalList::ORDER) {
                    $conditionalField = 'entity_id';
                }

                $entityIds = $this->getIdsInArchive(
                    $entity,
                    $orderIds
                );

                if (!empty($entityIds)) {
                    $this->removeFromArchive(
                        $entity,
                        $conditionalField,
                        $orderIds
                    );
                }
            }
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }

        return $orderIds;
    }

    /**
     * Update grid records
     *
     * @param string $archiveEntity
     * @param array $ids
     * @return $this
     */
    public function updateGridRecords($archiveEntity, $ids)
    {
        if (!$this->isArchiveEntityExists($archiveEntity) || empty($ids)) {
            return $this;
        }

        /* @var $resource \Magento\Framework\Model\ResourceModel\Db\AbstractDb */
        $resource = $this->_archivalList->getResource($archiveEntity);

        $gridColumns = array_keys(
            $this->getConnection()->describeTable($this->getArchiveEntityTable($archiveEntity))
        );

        $columnsToSelect = [];

        $select = $resource->getUpdateGridRecordsSelect($ids, $columnsToSelect, $gridColumns, true);

        $this->getConnection()->query(
            $select->insertFromSelect($this->getArchiveEntityTable($archiveEntity), $columnsToSelect, true)
        );

        return $this;
    }

    /**
     * Find related to order entity ids for checking of new items in archive
     *
     * @param string $archiveEntity
     * @param array $ids
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRelatedIds($archiveEntity, $ids)
    {
        if (empty($archiveEntity) || empty($ids)) {
            return [];
        }

        /** @var $resource \Magento\Framework\Model\ResourceModel\Db\AbstractDb */
        $resource = $this->_archivalList->getResource($archiveEntity);

        $select = $this->getConnection()->select()->from(
            ['main_table' => $resource->getMainTable()],
            'entity_id'
        )->joinInner(
            // Filter by archived order
            ['order_archive' => $this->getArchiveEntityTable('order')],
            'main_table.order_id = order_archive.entity_id',
            []
        )->where(
            'main_table.entity_id IN(?)',
            $ids
        );

        return $this->getConnection()->fetchCol($select);
    }
}
