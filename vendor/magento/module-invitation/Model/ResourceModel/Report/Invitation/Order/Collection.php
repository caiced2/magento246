<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reports invitation order report collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Invitation\Model\ResourceModel\Report\Invitation\Order;

class Collection extends \Magento\Invitation\Model\ResourceModel\Report\Invitation\Collection
{
    /**
     * Join custom fields
     *
     * @return $this
     */
    protected function _joinFields()
    {
        $acceptedExpr = 'SUM(' . $this->getConnection()->getCheckSql(
            'main_table.status = ' . $this->getConnection()->quote(
                \Magento\Invitation\Model\Invitation\Status::STATUS_ACCEPTED
            ) . 'AND main_table.referral_id IS NOT NULL',
            '1',
            '0'
        ) . ')';
        $canceledExpr = 'SUM(' . $this->getConnection()->getCheckSql(
            'main_table.status = ' . $this->getConnection()->quote(
                \Magento\Invitation\Model\Invitation\Status::STATUS_CANCELED
            ),
            '1',
            '0'
        ) . ')';

        $this->getSelect()->reset(
            \Magento\Framework\DB\Select::COLUMNS
        )->columns(
            ['sent' => new \Zend_Db_Expr('COUNT(main_table.invitation_id)')]
        )->columns(
            ['accepted' => new \Zend_Db_Expr($acceptedExpr)]
        )->columns(
            ['canceled' => new \Zend_Db_Expr($canceledExpr)]
        );

        return $this;
    }

    /**
     * Additional data manipulation after collection was loaded
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        foreach ($this->getItems() as $item) {
            if ($item->getSent()) {
                $item->setCanceledRate($item->getCanceled() / $item->getSent() * 100);
                $item->setAcceptedRate($item->getAccepted() / $item->getSent() * 100);
            } else {
                $item->setCanceledRate(0);
                $item->setAcceptedRate(0);
            }

            $item->setPurchased($this->getResource()->getPurchasedNumber(clone $this->getSelect()));

            if ($item->getAccepted()) {
                $item->setPurchasedRate($item->getPurchased() / $item->getSent() * 100);
            } else {
                $item->setPurchasedRate(0);
            }
        }

        return $this;
    }
}
