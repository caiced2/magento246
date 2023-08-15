<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey;

use Magento\Framework\DB\Adapter\AdapterInterface as Connection;

/**
 * Interface \Magento\Framework\ForeignKey\StrategyInterface
 *
 * @api
 * @deprecated split database solution is deprecated and will be removed
 */
interface StrategyInterface
{
    const TYPE_CASCADE = 'CASCADE';
    const TYPE_SET_NULL = 'SET NULL';
    const TYPE_RESTRICT = 'RESTRICT';
    const TYPE_NO_ACTION = 'NO ACTION';

    /**
     * Process constraints
     *
     * @param Connection $connection
     * @param ConstraintInterface $constraint
     * @param string $condition
     * @return void
     */
    public function process(Connection $connection, ConstraintInterface $constraint, $condition);

    /**
     * Lock affected data
     *
     * @param Connection $connection
     * @param string $table
     * @param string $condition
     * @param array $fields
     * @return array
     */
    public function lockAffectedData(Connection $connection, $table, $condition, $fields);
}
