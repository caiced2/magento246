<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Report\DataCount;

/**
 * Class ProductAttributes
 */
class ProductAttributes
{
    /**
     * @var \Magento\Eav\Model\ConfigFactory
     */
    protected $eavConfigFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    private $resource;

    /**
     * @param \Magento\Eav\Model\ConfigFactory $eavConfigFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category $catalogResource
     */
    public function __construct(
        \Magento\Eav\Model\ConfigFactory $eavConfigFactory,
        \Magento\Catalog\Model\ResourceModel\Category $catalogResource
    ) {
        $this->eavConfigFactory = $eavConfigFactory;
        $this->resource = $catalogResource;
    }

    /**
     * Calculate approximately the size of table row if using flat functionality based on product attributes list
     *
     * @return bool|int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductAttributesRowSizeForFlatTable()
    {
        /** @var \Magento\Eav\Model\Entity\Type $entityType */
        $entityType = $this->eavConfigFactory->create()->getEntityType(
            \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE
        );
        $entityTypeId = (int)$entityType->getId();

        $catalogEavAttributeTable = $this->resource->getTable('catalog_eav_attribute');
        $eavAttributeTable = $this->resource->getTable('eav_attribute');

        $info = $this->resource->getConnection()->fetchAll(
            "SELECT ea.`backend_type`
            FROM `{$catalogEavAttributeTable}` `main_table`
            INNER JOIN `{$eavAttributeTable}` ea ON
                (ea.`attribute_id` = main_table.`attribute_id` AND ea.`entity_type_id` = '{$entityTypeId}')"
        );

        /**
         * Dynamic EAV attributes
         *
         * @see http://dev.mysql.com/doc/refman/5.0/en/storage-requirements.html
         */
        $typeSizes = [
            'varchar'   => (255 + 1) * 3,
            'int'       => 4,
            'datetime'  => 8,
            'decimal'   => 4 + 2, // because decimal type = DECIMAL(12, 4)
        ];
        $result = 0;
        if (!$info) {
            return false;
        }

        $result = $this->calculateTableRowSize($info, $result, $typeSizes);

        /**
         * Static product entity attributes
         *
         * @see http://dev.mysql.com/doc/refman/5.0/en/storage-requirements.html
         */
        $typeSizes = [
            'tinyint'   => 1,
            'smallint'  => 2,
            'mediumint' => 3,
            'int'       => 4,
            'integer'   => 4,
            'bigint'    => 8,
            'float'     => 4,
            'double'    => 8,
            'real'      => 8,
            'date'      => 3,
            'time'      => 3,
            'datetime'  => 8,
            'timestamp' => 4,
            'year'      => 1,
        ];
        $table = $this->resource->getTable('catalog_product_entity');
        $description = $this->resource->getConnection()->describeTable($table);
        if (empty($description) || !is_array($description)) {
            return false;
        }

        $result = $this->calculateColumnDescriptionsSize($result, $description, $typeSizes);

        return (int)$result;
    }

    /**
     * Calculate the size of table row
     *
     * @param array $info
     * @param int $result
     * @param array $typeSizes
     * @return int
     */
    protected function calculateTableRowSize(array $info, $result, array $typeSizes)
    {
        $byType = [];
        foreach ($info as $data) {
            if ($data['backend_type'] == 'static') {
                continue;
            }
            if (!isset($byType[$data['backend_type']])) {
                $byType[$data['backend_type']] = 0;
            }
            $byType[$data['backend_type']]++;
        }
        foreach ($byType as $type => $count) {
            if (isset($typeSizes[$type])) {
                $result += $typeSizes[$type] * $count;
            }
        }

        return $result;
    }

    /**
     * Calculate table column descriptions size
     *
     * @param int $result
     * @param array $description
     * @param array $typeSizes
     * @return int
     */
    protected function calculateColumnDescriptionsSize($result, array $description, array $typeSizes)
    {
        foreach ($description as $column) {
            if (isset($typeSizes[$column['DATA_TYPE']])) {
                $result += $typeSizes[$column['DATA_TYPE']];
            } elseif ($column['DATA_TYPE'] == 'varchar') {
                $result += ($column['LENGTH'] + 1) * 3;
            } elseif ($column['DATA_TYPE'] == 'decimal') {
                $leftOver = $column['PRECISION'] - floor($column['PRECISION'] / 9) * 9;
                $result += floor($column['PRECISION'] / 9) * 4 + ceil($leftOver / 2);
            }
        }

        return $result;
    }
}
