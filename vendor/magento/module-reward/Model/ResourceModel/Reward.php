<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\ResourceModel;

use Magento\Reward\Model\Reward as ModelReward;

/**
 * Reward resource model
 *
 * @api
 * @since 100.0.2
 */
class Reward extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_reward', 'reward_id');
    }

    /**
     * Fetch reward by customer and website and set data to reward object
     *
     * @param ModelReward $reward
     * @param int $customerId
     * @param int $websiteId
     * @return $this
     */
    public function loadByCustomerId(ModelReward $reward, $customerId, $websiteId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable()
        )->where(
            'customer_id = :customer_id'
        )->where(
            'website_id = :website_id'
        );
        $bind = [':customer_id' => $customerId, ':website_id' => $websiteId];
        if ($data = $this->getConnection()->fetchRow($select, $bind)) {
            $reward->addData($data);
        }
        $this->_afterLoad($reward);
        return $this;
    }

    /**
     * Perform Row-level data update
     *
     * @param ModelReward $object
     * @param array $data New data
     * @return $this
     */
    public function updateRewardRow(ModelReward $object, $data)
    {
        if (!$object->getId() || !is_array($data)) {
            return $this;
        }
        $where = [$this->getIdFieldName() . '=?' => $object->getId()];
        $this->getConnection()->update($this->getMainTable(), $data, $where);
        return $this;
    }

    /**
     * Prepare orphan points by given website id and website base currency code
     * after website was deleted
     *
     * @param int $websiteId
     * @param string $baseCurrencyCode
     * @return $this
     */
    public function prepareOrphanPoints($websiteId, $baseCurrencyCode)
    {
        $connection = $this->getConnection();
        if ($websiteId) {
            $connection->update(
                $this->getMainTable(),
                ['website_id' => null, 'website_currency_code' => $baseCurrencyCode],
                ['website_id = ?' => $websiteId]
            );
        }
        return $this;
    }

    /**
     * Delete orphan (points of deleted website) points by given customer
     *
     * @param int $customerId
     * @return $this
     */
    public function deleteOrphanPointsByCustomer($customerId)
    {
        if ($customerId) {
            $this->getConnection()->delete(
                $this->getMainTable(),
                ['customer_id = ?' => $customerId, new \Zend_Db_Expr('website_id IS NULL')]
            );
        }
        return $this;
    }

    /**
     * Save salesrule reward points delta
     *
     * @param int $ruleId
     * @param int $pointsDelta
     * @return void
     */
    public function saveRewardSalesrule($ruleId, $pointsDelta)
    {
        $this->getConnection()->insertOnDuplicate(
            $this->getTable('magento_reward_salesrule'),
            ['rule_id' => $ruleId, 'points_delta' => $pointsDelta],
            ['points_delta']
        );
    }

    /**
     * Retrieve reward salesrule data by given rule Id or array of Ids
     *
     * @param int|array $rule
     * @return array
     */
    public function getRewardSalesrule($rule)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('magento_reward_salesrule')
        )->where(
            'rule_id IN (?)',
            $rule
        );
        if (is_array($rule)) {
            $data = $this->getConnection()->fetchAll($select);
        } else {
            $data = $this->getConnection()->fetchRow($select);
        }
        return $data;
    }
}
