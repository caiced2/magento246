<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Model\ResourceModel;

/**
 * Invitation data resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Invitation extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Intialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_invitation', 'invitation_id');
        $this->addUniqueField(
            ['field' => ['customer_id', 'email'], 'title' => __('Invitation for same email address')]
        );
    }

    /**
     * Save invitation tracking info
     *
     * @param int $inviterId
     * @param int $referralId
     * @return void
     */
    public function trackReferral($inviterId, $referralId)
    {
        $data = ['inviter_id' => (int)$inviterId, 'referral_id' => (int)$referralId];
        $this->getConnection()->insertOnDuplicate(
            $this->getTable('magento_invitation_track'),
            $data,
            array_keys($data)
        );
    }

    /**
     * Calculate number of purchased from invited customers
     *
     * @param \Magento\Framework\DB\Select $select
     * @return bool|int
     */
    public function getPurchasedNumber($select)
    {
        $purchasedCount = 0;
        $select->reset(
            \Magento\Framework\DB\Select::COLUMNS
        )->columns(
            ['main_table.referral_id', 'main_table.store_id']
        )->where('main_table.referral_id IS NOT NULL');
        $invitedCustomers = $this->getConnection()->fetchPairs($select);
        $invitedCustomerIds = array_keys($invitedCustomers);
        if (empty($invitedCustomerIds)) {
            return $purchasedCount;
        }
        /* var $select \Magento\Framework\DB\Select */
        $select->reset()->from(
            ['o' => $this->getTable('sales_order')],
            ['o.store_id', 'COUNT(DISTINCT o.customer_id) as cnt']
        )->where(
            $this->getConnection()->prepareSqlCondition('o.customer_id', ['in' => (array)$invitedCustomerIds])
        )->group(['o.store_id']);
        $result = $this->_resources->getConnection('sales')->fetchAssoc($select);

        foreach ($result as $storeId => $meta) {
            if (in_array($storeId, $invitedCustomers)) {
                $purchasedCount += $meta['cnt'];
            }
        }

        return $purchasedCount;
    }
}
