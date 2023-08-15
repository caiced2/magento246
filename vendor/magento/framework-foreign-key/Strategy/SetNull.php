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
class SetNull implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(Connection $connection, ConstraintInterface $constraint, $condition)
    {
        $connection->update(
            $constraint->getTableName(),
            [$constraint->getFieldName() => null],
            $condition
        );
    }

    /**
     * {@inheritdoc}
     */
    public function lockAffectedData(Connection $connection, $table, $condition, $fields)
    {
        $select = $connection->select()
            ->forUpdate(true)
            ->from($table, $fields)
            ->where($condition);

        $affectedData = $connection->fetchAssoc($select);
        return $affectedData;
    }
}
