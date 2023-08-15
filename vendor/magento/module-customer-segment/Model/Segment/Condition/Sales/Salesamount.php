<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Model\Segment\Condition\Sales;

/**
 * Orders amount condition
 */
class Salesamount extends \Magento\CustomerSegment\Model\Segment\Condition\Sales\Combine
{
    /**
     * Name of condition for displaying as html
     *
     * @var string
     */
    protected $frontConditionName = 'Sales Amount';

    /**
     * @param string $operator
     * @param string $value
     * @return \Zend_Db_Expr
     */
    protected function getConditionSql($operator, $value)
    {
        $connection = $this->getResource()->getConnection();
        $aggrFunc = $this->getAttribute() == 'total' ? 'SUM' : 'AVG';
        $firstIf = $connection->getCheckSql(
            $aggrFunc . '(sales_order.base_grand_total) IS NOT NULL',
            $aggrFunc . '(sales_order.base_grand_total)',
            0
        );
        return new \Zend_Db_Expr($connection->getCheckSql($firstIf . ' ' . $operator . ' ' . $value, 1, 0));
    }
}
