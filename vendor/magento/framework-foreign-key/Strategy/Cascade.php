<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey\Strategy;

use Magento\Framework\DB\Adapter\AdapterInterface as Connection;
use Magento\Framework\ForeignKey\ConstraintInterface;
use Magento\Framework\ForeignKey\StrategyInterface;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class Cascade implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(Connection $connection, ConstraintInterface $constraint, $condition)
    {
        $connection->delete($constraint->getTableName(), $condition);
    }

    /**
     * {@inheritdoc}
     */
    public function lockAffectedData(Connection $connection, $table, $condition, $fields)
    {
        $selectObject = $connection->select();
        $selectObject->forUpdate(true);
        $selectObject->from($table, $fields);
        $selectObject->where($condition);
        $affectedData = $connection->fetchAssoc($selectObject);
        return $affectedData;
    }
}
