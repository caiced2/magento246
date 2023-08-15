<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Category\Position;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\DB\Ddl\Table;

/**
 * Create in-memory temporary table with product ids and positions
 */
class TemporaryTableFactory
{
    private const PREFIX = 'tmp_catalog_category_product_position';
    private const COLUMN_PRODUCT_ID = 'product_id';
    private const COLUMN_POSITION = 'position';
    private const BATCH_SIZE = 10000;

    /**
     * @var Product
     */
    private $resource;

    /**
     * @param Product $resource
     */
    public function __construct(
        Product $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * Create temporary table
     *
     * @param array $data
     * @return string
     */
    public function create(array $data): string
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTable(str_replace('.', '_', uniqid(self::PREFIX, true)));
        $table = $connection->newTable($tableName);
        $table->addColumn(
            self::COLUMN_PRODUCT_ID,
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false, 'primary' => true],
            'Product ID'
        );
        $table->addColumn(
            self::COLUMN_POSITION,
            Table::TYPE_INTEGER,
            10,
            ['unsigned' => true, 'nullable' => false],
            'Position'
        );
        $table->setOption('type', 'memory');
        $connection->createTemporaryTable($table);
        $this->populate($tableName, $data);

        return $tableName;
    }

    /**
     * Populate temporary table
     *
     * @param string $table
     * @param array $data
     */
    private function populate(string $table, array $data): void
    {
        $connection = $this->resource->getConnection();
        $tmpTableName = $this->resource->getTable($table);
        foreach (array_chunk($data, self::BATCH_SIZE, true) as $chunk) {
            $insertData = [];
            foreach ($chunk as $productId => $position) {
                $insertData[] = [
                    $productId,
                    $position
                ];
            }
            $connection->insertArray(
                $tmpTableName,
                [
                    self::COLUMN_PRODUCT_ID,
                    self::COLUMN_POSITION,
                ],
                $insertData
            );
        }
    }
}
