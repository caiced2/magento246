<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\ResourceModel;

use Magento\Framework\DB\Sequence\SequenceInterface;
use Magento\Framework\App\ResourceConnection;

class Sequence implements SequenceInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var string
     */
    protected $sequenceTableName;

    /**
     * @param ResourceConnection $resource
     * @param string $sequenceTableName
     */
    public function __construct(
        ResourceConnection $resource,
        $sequenceTableName
    ) {
        $this->resource = $resource;
        $this->sequenceTableName = $sequenceTableName;
    }

    /**
     * @inheritdoc
     */
    public function getNextValue()
    {
        $tableName = $this->resource->getTableName($this->sequenceTableName);
        $this->resource->getConnection()->insert($tableName, []);
        return $this->resource->getConnection()->lastInsertId($tableName);
    }

    /**
     * @inheritdoc
     */
    public function getCurrentValue()
    {
        $select = $this->resource->getConnection()->select();
        $select->from($this->resource->getTableName($this->sequenceTableName));
        return $this->resource->getConnection()->fetchRow($select);
    }
}
