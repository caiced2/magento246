<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Model\ResourceModel;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Logging event resource model
 */
class Event extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $directory;

    /**
     * Archive factory
     *
     * @var \Magento\Logging\Model\ArchiveFactory
     */
    protected $_archiveFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Logging\Model\ArchiveFactory $archiveFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Logging\Model\ArchiveFactory $archiveFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_archiveFactory = $archiveFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->dateTime = $dateTime;
    }

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_logging_event', 'log_id');
    }

    /**
     * Convert data before save ip
     *
     * @param \Magento\Framework\Model\AbstractModel $event
     * @return $this|\Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $event)
    {
        $event->setTime($this->dateTime->formatDate($event->getTime()));
        return $this;
    }

    /**
     * Rotate logs - get from database and pump to CSV-file
     *
     * @param int $lifetime
     * @return void
     */
    public function rotate($lifetime)
    {
        $connection = $this->getConnection();

        // get the latest log entry required to the moment
        $clearBefore = $this->dateTime->formatDate(time() - $lifetime);

        $select = $connection->select()->from(
            $this->getMainTable(),
            'log_id'
        )->where(
            'time < ?',
            $clearBefore
        )->order(
            'log_id DESC'
        )->limit(
            1
        );
        $latestLogEntry = $connection->fetchOne($select);
        if ($latestLogEntry) {
            // make sure folder for dump file will exist
            /** @var \Magento\Logging\Model\Archive $archive */
            $archive = $this->_archiveFactory->create();
            $archive->createNew();

            $expr = new \Zend_Db_Expr('INET_NTOA(' . $this->getConnection()->quoteIdentifier('ip') . ')');
            $select = $connection->select()->from(
                $this->getMainTable()
            )->where(
                'log_id <= ?',
                $latestLogEntry
            )->columns(
                $expr
            );

            $rows = $connection->fetchAll($select);

            $path = $this->directory->getRelativePath($archive->getFilename());
            $stream = $this->directory->openFile($path, 'w');
            // dump all records before this log entry into a CSV-file
            foreach ($rows as $row) {
                $stream->writeCsv($row);
            }
            $stream->close();

            $connection->delete($this->getMainTable(), ['log_id <= ?' => $latestLogEntry]);
        }
    }

    /**
     * Select all values of specified field from main table
     *
     * @param string $field
     * @param bool $order
     * @return array
     */
    public function getAllFieldValues($field, $order = true)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->distinct(true)->from($this->getMainTable(), $field);
        if ($order !== null) {
            $select->order($field . ($order ? '' : ' DESC'));
        }
        return $connection->fetchCol($select);
    }

    /**
     * Get all admin user names that are currently in event log table
     *
     * @return array
     */
    public function getUserNames()
    {
        $connection = $this->getConnection();
        $select = $connection->select()->distinct()->from(
            ['admins' => $this->getTable('admin_user')],
            'username'
        )->joinInner(
            ['events' => $this->getTable('magento_logging_event')],
            'admins.username = events.' . $connection->quoteIdentifier('user'),
            []
        );
        return $connection->fetchCol($select);
    }

    /**
     * Get event change ids of specified event
     *
     * @param int $eventId
     * @return array
     */
    public function getEventChangeIds($eventId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getTable('magento_logging_event_changes'),
            ['id']
        )->where(
            'event_id = ?',
            $eventId
        );
        return $connection->fetchCol($select);
    }
}
