<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\ResourceModel;

/**
 * Represents product sequence logic.
 */
class ProductSequence implements \Magento\Framework\DB\Sequence\SequenceInterface
{
    /**
     * Sequence table name
     */
    const SEQUENCE_TABLE = 'sequence_product';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @inheritdoc
     */
    public function getNextValue()
    {
        $tableName = $this->resource->getTableName(static::SEQUENCE_TABLE);
        $this->resource->getConnection()->insert($tableName, []);
        return $this->resource->getConnection()->lastInsertId($tableName);
    }

    /**
     * @inheritdoc
     */
    public function getCurrentValue()
    {
        $select = $this->resource->getConnection()->select();
        $select->from($this->resource->getTableName(static::SEQUENCE_TABLE));
        return $this->resource->getConnection()->fetchRow($select);
    }
}
