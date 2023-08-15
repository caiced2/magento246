<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

class ExtendSalesAmountExpressionObserver implements ObserverInterface
{
    /**
     * Extend sales amount expression with customer balance refunded value
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $expressionTransferObject \Magento\Framework\DataObject */
        $expressionTransferObject = $observer->getEvent()->getExpressionObject();
        /** @var $connection \Magento\Framework\DB\Adapter\AdapterInterface */
        $connection = $observer->getEvent()->getCollection()->getConnection();
        $expressionTransferObject->setExpression($expressionTransferObject->getExpression() . ' + (%s)');
        $arguments = $expressionTransferObject->getArguments();
        $arguments[] = $connection->getCheckSql(
            $connection->prepareSqlCondition('main_table.bs_customer_bal_total_refunded', ['null' => null]),
            0,
            sprintf(
                'main_table.bs_customer_bal_total_refunded - %s - %s',
                $connection->getIfNullSql('main_table.base_tax_refunded', 0),
                $connection->getIfNullSql('main_table.base_shipping_refunded', 0)
            )
        );

        $expressionTransferObject->setArguments($arguments);
    }
}
