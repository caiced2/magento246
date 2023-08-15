<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\ResourceModel\Reward;

use Magento\Reward\Model\Reward\History as ModelRewardHistory;

/**
 * Reward history resource model
 *
 * @api
 * @since 100.0.2
 */
class History extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Date time formatter
     *
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        $connectionName = null
    ) {
        $this->dateTime = $dateTime;
        parent::__construct($context, $connectionName);
    }

    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_reward_history', 'history_id');
        $this->_serializableFields = ['additional_data' => [[], []]];
    }

    /**
     * Check if history update with given action, customer and entity exist
     *
     * @param int $customerId
     * @param int $action
     * @param int $websiteId
     * @param mixed $entity
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isExistHistoryUpdate($customerId, $action, $websiteId, $entity)
    {
        if ($entity === null) {
            return false;
        }
        $select = $this->getConnection()->select()->from(
            ['reward_table' => $this->getTable('magento_reward')],
            []
        )->joinInner(
            ['history_table' => $this->getMainTable()],
            'history_table.reward_id = reward_table.reward_id',
            []
        )->where(
            'history_table.action = :action'
        )->where(
            'history_table.website_id = :website_id'
        )->where(
            'history_table.entity = :entity'
        )->columns(
            ['COUNT(history_table.history_id)']
        );
        $bind = ['action' => $action, 'website_id' => $websiteId, 'entity' => $entity];
        if ((int)$this->getConnection()->fetchOne($select, $bind) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Return total quantity rewards for specified action and customer
     *
     * @param int $action
     * @param int $customerId
     * @param int $websiteId
     * @return int
     */
    public function getTotalQtyRewards($action, $customerId, $websiteId)
    {
        $select = $this->getConnection()->select()->from(
            ['history_table' => $this->getMainTable()],
            ['COUNT(*)']
        )->joinInner(
            ['reward_table' => $this->getTable('magento_reward')],
            'history_table.reward_id = reward_table.reward_id',
            []
        )->where(
            'history_table.action=:action'
        )->where(
            'reward_table.customer_id=:customer_id'
        )->where(
            'history_table.website_id=:website_id'
        );
        $bind = ['action' => $action, 'customer_id' => $customerId, 'website_id' => $websiteId];
        return (int)$this->getConnection()->fetchOne($select, $bind);
    }

    /**
     * Retrieve actual history records that have unused points, i.e. points_delta-points_used > 0
     *
     * Update points_used field for non-used points
     *
     * @param ModelRewardHistory $history
     * @param int $required Points total that required
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function useAvailablePoints($history, $required)
    {
        $required = (int)abs($required);
        if (!$required) {
            return $this;
        }
        $connection = $this->getConnection();
        try {
            $connection->beginTransaction();
            $select = $connection->select()->from(
                ['history' => $this->getMainTable()],
                ['history_id', 'points_delta', 'points_used']
            )->where(
                'reward_id = :reward_id'
            )->where(
                'website_id = :website_id'
            )->where(
                'is_expired=0'
            )->where(
                'points_delta - points_used > 0'
            )->order(
                'history_id'
            )->forUpdate(
                true
            );
            $bind = [':reward_id' => $history->getRewardId(), ':website_id' => $history->getWebsiteId()];

            $stmt = $connection->query($select, $bind);

            $updateSqlValues = [];
            $data = [];
            while ($row = $stmt->fetch()) {
                if ($required <= 0) {
                    break;
                }
                $rowAvailable = $row['points_delta'] - $row['points_used'];
                $pointsUsed = min($required, $rowAvailable);
                $required -= $pointsUsed;
                $newPointsUsed = $pointsUsed + $row['points_used'];
                $data[] = ['history_id' => $row['history_id'], 'points_used' => $newPointsUsed];
            }

            if (count($data) > 0) {
                $connection->insertOnDuplicate($this->getMainTable(), $data, ['history_id', 'points_used']);
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }

        return $this;
    }

    /**
     * Update history expired_at_dynamic field for specified websites when config changed
     *
     * @param int $days Reward Points Expire in (days)
     * @param array $websiteIds Array of website ids that must be updated
     * @return $this
     */
    public function updateExpirationDate($days, $websiteIds)
    {
        $connection = $this->getConnection();
        $websiteIds = is_array($websiteIds) ? $websiteIds : [$websiteIds];
        $days = (int)abs($days);
        $update = [];
        if ($days) {
            $update['expired_at_dynamic'] = $connection->getDateAddSql(
                'created_at',
                $days,
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_DAY
            );
        } else {
            $update['expired_at_dynamic'] = new \Zend_Db_Expr('NULL');
        }
        $where = ['website_id IN (?)' => $websiteIds];
        $connection->update($this->getMainTable(), $update, $where);
        return $this;
    }

    /**
     * Make points expired for specified website
     *
     * @param int $websiteId
     * @param string $expiryType Expiry calculation (static or dynamic)
     * @param int $limit Limitation for records expired selection
     * @return $this
     */
    public function expirePoints($websiteId, $expiryType, $limit)
    {
        $connection = $this->getConnection();
        $now = $this->dateTime->formatDate(time());
        $field = $expiryType == 'static' ? 'expired_at_static' : 'expired_at_dynamic';

        $select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            'website_id = :website_id'
        )->where(
            "{$field} < :time_now"
        )->where(
            "{$field} IS NOT NULL"
        )->where(
            'is_expired=?',
            0
        )->where(
            'points_delta-points_used > ?',
            0
        )->limit(
            (int)$limit
        );
        $bind = [':website_id' => $websiteId, ':time_now' => $now];
        $duplicates = [];
        $expiredAmounts = [];
        $expiredHistoryIds = [];
        $stmt = $connection->query($select, $bind);
        while ($row = $stmt->fetch()) {
            $row['created_at'] = $now;
            $row['expired_at_static'] = null;
            $row['expired_at_dynamic'] = null;
            $row['is_expired'] = '1';
            $row['is_duplicate_of'] = $row['history_id'];
            $expiredHistoryIds[] = $row['history_id'];
            unset($row['history_id']);
            if (!isset($expiredAmounts[$row['reward_id']])) {
                $expiredAmounts[$row['reward_id']] = 0;
            }
            $expiredAmount = $row['points_delta'] - $row['points_used'];
            $row['points_delta'] = -$expiredAmount;
            $row['points_used'] = 0;
            $expiredAmounts[$row['reward_id']] += $expiredAmount;
            $duplicates[] = $row;
        }

        if (count($expiredHistoryIds) > 0) {
            // decrease points balance of rewards
            foreach ($expiredAmounts as $rewardId => $expired) {
                if ($expired == 0) {
                    continue;
                }
                $bind = [
                    'points_balance' => $connection->getCheckSql(
                        "points_balance > {$expired}",
                        "points_balance-{$expired}",
                        0
                    ),
                ];
                $where = ['reward_id=?' => $rewardId];
                $connection->update($this->getTable('magento_reward'), $bind, $where);
            }

            // duplicate expired records
            $connection->insertMultiple($this->getMainTable(), $duplicates);

            // update is_expired field (using history ids instead where clause for better performance)
            $connection->update(
                $this->getMainTable(),
                ['is_expired' => '1'],
                ['history_id IN (?)' => $expiredHistoryIds]
            );
        }

        return $this;
    }

    /**
     * Mark history records as notification was sent to customer (about points expiration)
     *
     * @param array $ids
     * @return $this
     */
    public function markAsNotified($ids)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['notification_sent' => 1],
            ['history_id IN (?)' => $ids]
        );
        return $this;
    }

    /**
     * Perform Row-level data update
     *
     * @param ModelRewardHistory $object
     * @param array $data New data
     * @return $this
     */
    public function updateHistoryRow(ModelRewardHistory $object, $data)
    {
        if (!$object->getId() || !is_array($data)) {
            return $this;
        }
        $where = [$this->getIdFieldName() . '=?' => $object->getId()];
        $this->getConnection()->update($this->getMainTable(), $data, $where);
        return $this;
    }
}
