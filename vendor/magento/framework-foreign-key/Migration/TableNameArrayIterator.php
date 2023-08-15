<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Migration;

use Magento\Framework\App\ResourceConnection;

/**
 * This iterator used to iterate table names. Table names passed to constructor without prefixes
 * and have to be modified by resource to full table name format.
 *
 * @deprecated split database solution is deprecated and will be removed
 * @see \Magento\Framework\App\ResourceConnection::getTableName
 */
class TableNameArrayIterator extends \ArrayIterator
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     * @param array $tableNames
     * @param int $flags
     */
    public function __construct(ResourceConnection $resourceConnection, array $tableNames = [], $flags = 0)
    {
        parent::__construct($tableNames, $flags);
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Return current array entry
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->resourceConnection->getTableName(parent::current());
    }
}
