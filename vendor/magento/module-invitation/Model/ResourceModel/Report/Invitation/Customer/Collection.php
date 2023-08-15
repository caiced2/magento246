<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reports invitation customer report collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Invitation\Model\ResourceModel\Report\Invitation\Customer;

class Collection extends \Magento\Reports\Model\ResourceModel\Customer\Collection
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
        $this->getSelect()->join(
            ['invitation' => $this->getTable('magento_invitation')],
            'invitation.customer_id = e.entity_id',
            [
                'sent' => new \Zend_Db_Expr('COUNT(invitation.invitation_id)'),
                'accepted' => new \Zend_Db_Expr('COUNT(invitation.referral_id) ')
            ]
        )->group(
            'e.entity_id'
        );

        $this->_joinFields['invitation_store_id'] = ['table' => 'invitation', 'field' => 'store_id'];
        $this->_joinFields['invitation_date'] = ['table' => 'invitation', 'field' => 'invitation_date'];

        // Filter by date range
        $this->addFieldToFilter('invitation_date', ['from' => $fromDate, 'to' => $toDate, 'time' => true]);

        // Add customer name
        $this->addNameToSelect();

        // Add customer group
        $this->addAttributeToSelect('group_id', 'inner');
        $this->joinField('group_name', 'customer_group', 'customer_group_code', 'customer_group_id=group_id');

        $this->orderByCustomerRegistration();
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
            $this->addFieldToFilter('invitation_store_id', ['in' => (array)$storeIds]);
        }
        return $this;
    }
}
