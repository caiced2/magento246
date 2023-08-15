<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reports invitation report collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Invitation\Model\ResourceModel\Report\Invitation;

class Collection extends \Magento\Invitation\Model\ResourceModel\Invitation\Collection
{
    /**
     * Joins Invitation report data, and filter by date
     *
     * @param \DateTime|string $fromDate
     * @param \DateTime|string $toDate
     * @return $this
     */
    public function setDateRange($fromDate, $toDate)
    {
        $this->_reset();

        $canceledField = $this->getConnection()->getCheckSql(
            'main_table.status = ' . $this->getConnection()->quote(
                \Magento\Invitation\Model\Invitation\Status::STATUS_CANCELED
            ),
            '1',
            '0'
        );

        $canceledRate = $this->getConnection()->getCheckSql(
            'COUNT(main_table.invitation_id) = 0',
            '0',
            'SUM(' . $canceledField . ') / COUNT(main_table.invitation_id) * 100'
        );

        $acceptedRate = $this->getConnection()->getCheckSql(
            'COUNT(main_table.invitation_id) = 0',
            '0',
            'COUNT(DISTINCT main_table.referral_id) / COUNT(main_table.invitation_id) * 100'
        );

        $this->addFieldToFilter(
            'invitation_date',
            ['from' => $fromDate, 'to' => $toDate, 'time' => true]
        )->getSelect()->reset(
            \Magento\Framework\DB\Select::COLUMNS
        )->columns(
            [
                'sent' => new \Zend_Db_Expr('COUNT(main_table.invitation_id)'),
                'accepted' => new \Zend_Db_Expr('COUNT(DISTINCT main_table.referral_id)'),
                'canceled' => new \Zend_Db_Expr('SUM(' . $canceledField . ') '),
                'canceled_rate' => $canceledRate,
                'accepted_rate' => $acceptedRate,
            ]
        );

        $this->_joinFields();

        return $this;
    }

    /**
     * Join custom fields
     *
     * @return $this
     */
    protected function _joinFields()
    {
        return $this;
    }

    /**
     * Filters report by stores
     *
     * @param int[] $storeIds
     * @return $this
     */
    public function setStoreIds($storeIds)
    {
        if ($storeIds) {
            $this->addFieldToFilter('main_table.store_id', ['in' => (array)$storeIds]);
        }
        return $this;
    }
}
