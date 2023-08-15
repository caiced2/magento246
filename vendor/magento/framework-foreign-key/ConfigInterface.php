<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey;

/**
 * Interface \Magento\Framework\ForeignKey\ConfigInterface
 *
 * @api
 * @deprecated split database solution is deprecated and will be removed
 */
interface ConfigInterface
{
    /**
     * Get constraints by reference table name
     *
     * @param string $referenceTableName
     * @return ConstraintInterface[]
     */
    public function getConstraintsByReferenceTableName($referenceTableName);

    /**
     * Get constraints by table name
     *
     * @param string $tableName
     * @return ConstraintInterface[]
     */
    public function getConstraintsByTableName($tableName);
}
