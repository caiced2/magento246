<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey\Strategy;

use Magento\Framework\DB\Adapter\AdapterInterface as Connection;
use Magento\Framework\ForeignKey\StrategyInterface;
use Magento\Framework\ForeignKey\ConstraintInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class Restrict implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(Connection $connection, ConstraintInterface $constraint, $condition)
    {
        throw new LocalizedException(
            new Phrase(
                "The row couldn't be updated because a foreign key constraint failed. "
                . "Verify the constraint and try again."
            )
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

        if (!empty($affectedData)) {
            throw new LocalizedException(
                new Phrase(
                    "The row couldn't be updated because a foreign key constraint failed. "
                    . "Verify the constraint and try again."
                )
            );
        }
        return $affectedData;
    }
}
