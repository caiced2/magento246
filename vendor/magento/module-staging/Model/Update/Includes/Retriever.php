<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Update\Includes;

use Magento\Framework\App\ResourceConnection;
use Magento\Staging\Model\Update\IncludesList;
use Magento\Staging\Model\StagingList;

class Retriever
{
    /**
     * @var StagingList
     */
    protected $stagingList;

    /**
     * @var IncludesList
     */
    protected $includesList;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @param StagingList $stagingList
     * @param IncludesList $includesList
     * @param ResourceConnection $resource
     */
    public function __construct(
        StagingList $stagingList,
        IncludesList $includesList,
        ResourceConnection $resource
    ) {
        $this->stagingList = $stagingList;
        $this->includesList = $includesList;
        $this->resource = $resource;
    }

    /**
     * Retrieve all includes to update
     *
     * @param array $ids
     * @return array
     * @throws \Zend_Db_Select_Exception
     */
    public function getIncludes(array $ids)
    {
        $selects = [];
        $includesTypes = $this->includesList->getIncludesTypes();
        foreach ($this->stagingList->getEntitiesTables() as $entityType => $entityTable) {
            if (!isset($includesTypes[$entityType])) {
                continue;
            }
            $includesType = $includesTypes[$entityType];

            /** @var \Magento\Framework\DB\Select $select */
            $select = clone $this->resource->getConnection()->select();
            $select->reset();
            $select->setPart('disable_staging_preview', true);
            $selects[] = $select->from(
                [$entityTable],
                [
                    'created_in',
                    'includes' => $includesType->getCountSql(),
                    'entity_type' => new \Zend_Db_Expr($this->resource->getConnection()->quote($entityType))
                ]
            )->where(
                'created_in IN (?)',
                $ids
            )->group(
                $includesType->getGroupByFields()
            )->having('includes > 0');
        }
        $sql = $this->resource->getConnection()->select()->union($selects, \Magento\Framework\DB\Select::SQL_UNION_ALL);
        return $this->resource->getConnection()->fetchAll($sql);
    }
}
