<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Select;

use Magento\Framework\DB\Select;
use Magento\Framework\DB\Platform\Quote;
use Magento\Staging\Model\StagingList;
use Magento\Staging\Model\VersionManager\Proxy as VersionManager;

/**
 * Class FromRenderer
 */
class FromRenderer extends \Magento\Framework\DB\Select\FromRenderer
{
    /**
     * @var array
     */
    protected $stagedTables;

    /**
     * @var int
     */
    protected $versionId;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * FromRenderer constructor.
     *
     * @param Quote $quote
     * @param StagingList $stagingList
     * @param VersionManager $versionManager
     */
    public function __construct(
        Quote $quote,
        StagingList $stagingList,
        VersionManager $versionManager
    ) {
        parent::__construct($quote);
        $this->stagedTables = array_flip($stagingList->getEntitiesTables());
        $this->versionManager = $versionManager;
    }

    /**
     * Render FROM & JOIN's section
     *
     * @param Select $select
     * @param string $sql
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function render(Select $select, $sql = '')
    {
        /*
         * If no table specified, use RDBMS-dependent solution
         * for table-less query.  e.g. DUAL in Oracle.
         */
        $source = $select->getPart(Select::FROM);
        if (empty($source)) {
            $source = [];
        }
        $from = [];
        foreach ($source as $correlationName => $table) {
            $tmp = '';

            $joinType = ($table['joinType'] == Select::FROM) ? Select::INNER_JOIN : $table['joinType'];
            // Add join clause (if applicable)
            if (!empty($from)) {
                $tmp .= ' ' . strtoupper($joinType) . ' ';
            }
            $tmp .= $this->getQuotedSchema($table['schema']);
            $tmp .= $this->getQuotedTable($table['tableName'], $correlationName);

            // Add join conditions (if applicable)
            if (!empty($from) && !empty($table['joinCondition'])) {
                $tmp .= ' ' . Select::SQL_ON . ' ' . $table['joinCondition'];
            }
            if (is_string($table['tableName'])
                && isset($this->stagedTables[$table['tableName']])
                && !$select->getPart('disable_staging_preview')
            ) {
                $alias = $correlationName ?: $table;
                $versionId = $this->versionManager->getVersion()->getId();

                $createdIn = $select->getAdapter()->quoteInto($alias . '.created_in <= ?', $versionId);
                $updatedIn = $select->getAdapter()->quoteInto($alias . '.updated_in > ?', $versionId);

                if (!empty($from) && !empty($table['joinCondition'])) {
                    $tmp .= ' ' . Select::SQL_AND . ' (' . $createdIn . ' ' . Select::SQL_AND . ' ' . $updatedIn . ')';
                } else {
                    $wherePart = $select->getPart(Select::WHERE);
                    if (!in_array(Select::SQL_AND . ' (' . $updatedIn . ')', $wherePart)) {
                        // In case it uses OR, we must reset and add back with parenthesis.
                        // Otherwise: WHERE foo = 1 OR bar = 2 AND created_in <= ? AND updated_in > ?
                        if (!empty($wherePart)) {
                            $select->reset(Select::WHERE);
                            $select->where(
                                implode(' ', $wherePart),
                                null,
                                Select::TYPE_CONDITION
                            );
                        }
                        $select->where($alias . '.created_in <= ?', $versionId);
                        $select->where($alias . '.updated_in > ?', $versionId);
                    }
                }
            }

            // Add the table name and condition add to the list
            $from[] = $tmp;
        }
        // Add the list of all joins
        if (!empty($from)) {
            $sql .= ' ' . Select::SQL_FROM . ' ' . implode("\n", $from);
        }
        return $sql;
    }
}
