<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ScalableOms\Model;

use Iterator;
use Magento\Framework\App\ResourceConnection;
use OuterIterator;

/**
 * This iterator iterates sales sequence tables.
 * For split databases default connection is used.
 *
 * @deprecated split database solution is deprecated and will be removed
 */
class SequenceTableIterator implements OuterIterator
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Iterator
     */
    private $internalIterator;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $connectionName
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        $connectionName = ResourceConnection::DEFAULT_CONNECTION
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->connectionName = $connectionName;
    }

    /**
     * @inheritdoc
     */
    #[\ReturnTypeWillChange]
    public function getInnerIterator()
    {
        if (!$this->internalIterator) {
            $connection = $this->resourceConnection->getConnection($this->connectionName);
            $sequenceMetaTable = $this->resourceConnection->getTableName('sales_sequence_meta');
            $sequenceTables = [];
            $fetchedData = $connection->query(
                $connection->select()->from(
                    $sequenceMetaTable,
                    ['entity_type', 'store_id']
                )
            )->fetchAll();
            foreach ($fetchedData as $sequenceTableData) {
                $sequenceTables[] = sprintf(
                    'sequence_%s_%s',
                    $sequenceTableData['entity_type'],
                    $sequenceTableData['store_id']
                );
            }

            $this->internalIterator = new \ArrayIterator($sequenceTables);
        }

        return $this->internalIterator;
    }

    /**
     * @inheritdoc
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->resourceConnection->getTableName($this->getInnerIterator()->current());
    }

    /**
     * @inheritdoc
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->getInnerIterator()->next();
    }

    /**
     * @inheritdoc
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->getInnerIterator()->key();
    }

    /**
     * @inheritdoc
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->getInnerIterator()->valid();
    }

    /**
     * @inheritdoc
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->getInnerIterator()->rewind();
    }
}
