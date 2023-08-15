<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Staging\Setup\Declaration\Schema\Db\MySQL\DDL\Triggers;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\SelectFactory;
use Magento\Framework\Setup\Declaration\Schema\Db\DDLTriggerInterface;
use Magento\Framework\Setup\Declaration\Schema\Dto\Column;
use Magento\Framework\Setup\Declaration\Schema\ElementHistory;

/**
 * Used to migrate data from original table identity column to sequence table.
 *
 * Can add statement in case when data can`t be migrate easily.
 */
class MigrateSequenceColumnData implements DDLTriggerInterface
{
    /**
     * Pattern with which we can match whether we can apply and use this trigger or not.
     */
    const MATCH_PATTERN = '/migrateSequneceColumnData\(([^\)]+)\,([^\)]+)\)/';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SelectFactory
     */
    private $selectFactory;

    /**
     * Constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param SelectFactory $selectFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SelectFactory $selectFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->selectFactory = $selectFactory;
    }

    /**
     * @inheritdoc
     */
    public function isApplicable(string $statement) : bool
    {
        return (bool) preg_match(self::MATCH_PATTERN, $statement);
    }

    /**
     * @inheritdoc
     */
    public function getCallback(ElementHistory $elementHistory) : callable
    {
        /** @var Column $column */
        $column = $elementHistory->getNew();
        preg_match(self::MATCH_PATTERN, $column->getOnCreate(), $matches);
        return function () use ($column, $matches) {
            $tableName = $column->getTable()->getName();
            $tableMigrateFrom = $this->resourceConnection->getTableName($matches[1]);
            $columnMigrateFrom = $matches[2];
            $adapter = $this->resourceConnection->getConnection(
                $column->getTable()->getResource()
            );
            $select = $this->selectFactory->create($adapter);
            $select
                ->from(
                    $tableMigrateFrom,
                    [$column->getName() => $columnMigrateFrom]
                )
                ->setPart('disable_staging_preview', true);
            // Migrate data only if table exists.
            // If origin table does not exist sequence data remains empty.
            if ($adapter->isTableExists($tableMigrateFrom)) {
                $adapter->query(
                    $adapter->insertFromSelect(
                        $select,
                        $this->resourceConnection->getTableName($tableName)
                    )
                );
            }
        };
    }
}
