<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Data;

/**
 * Report Corrupted Categories Data
 */
class CorruptedCategoriesDataSection extends AbstractDataGroup
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        return [
            (string)__('Corrupted Categories Data') => [
                'headers' => [
                    __('Id'), __('Expected Children Count'), __('Actual Children Count'),
                    __('Expected Level'), __('Actual Level')
                ],
                'data' => $this->getCorruptedCategories()
            ]
        ];
    }

    /**
     * Get corrupted categories
     *
     * @return array
     */
    protected function getCorruptedCategories()
    {
        $data = [];
        try {
            $categoryTable = $this->resource->getTable('catalog_category_entity');

            $expectedData = $this->getExpectedCategoriesData($categoryTable);
            $actualData = $this->getActualCategoriesData($categoryTable);

            foreach ($actualData as $categoryId => $categoryData) {
                $actualChildrenCount = $categoryData['children_count'];
                $actualLevel = $categoryData['level'];

                if (!array_key_exists($categoryId, $expectedData)) {
                    $data[] = [
                        $categoryId,
                        self::NOT_AVAILABLE_DATA,
                        $actualChildrenCount,
                        self::NOT_AVAILABLE_DATA,
                        $actualLevel
                    ];
                    continue;
                }

                $expectedChildrenCount = $expectedData[$categoryId]['children_count'];
                $expectedLevel = $expectedData[$categoryId]['level'];

                if ($actualChildrenCount == $expectedChildrenCount && $actualLevel == $expectedLevel) {
                    continue;
                }

                $data[] = [
                    $categoryId,
                    $expectedChildrenCount,
                    $this->getActualChildrenCount($actualChildrenCount, $expectedChildrenCount),
                    $expectedLevel,
                    $this->getActualLevel($actualLevel, $expectedLevel)
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return $data;
    }

    /**
     * Get actual level of category with difference from expected level
     *
     * @param int $actualLevel
     * @param int $expectedLevel
     * @return int|string
     */
    protected function getActualLevel($actualLevel, $expectedLevel)
    {
        $difference = $actualLevel - $expectedLevel;
        if ($difference != 0) {
            $difference = ' (diff: ' . ($difference > 0 ? '+' : '') . $difference . ')';
        } else {
            $difference = '';
        }
        $actualLevel .= $difference;

        return $actualLevel;
    }

    /**
     * Get actual children count of category with difference from expected
     *
     * @param int $actualChildrenCount
     * @param int $expectedChildrenCount
     * @return int|string
     */
    protected function getActualChildrenCount($actualChildrenCount, $expectedChildrenCount)
    {
        $difference = $actualChildrenCount - $expectedChildrenCount;
        if ($difference != 0) {
            $difference = ' (diff: ' . ($difference > 0 ? '+' : '') . $difference . ')';
        } else {
            $difference = '';
        }
        $actualChildrenCount .= $difference;

        return $actualChildrenCount;
    }

    /**
     * Get expected data of categories
     *
     * @param string $tableName Table name, processes by resource->getTable
     * @return array
     */
    protected function getExpectedCategoriesData($tableName)
    {
        $data = [];

        $select = $this->connection->select()
            ->from(
                ['c' => $tableName],
                [
                    'c.entity_id',
                    'children_count' => new \Zend_Db_Expr('COUNT(c2.children_count)'),
                    'level' => new \Zend_Db_Expr('(LENGTH(c.path) - LENGTH(REPLACE(c.path,\'/\',\'\')))')
                ]
            )
            ->joinLeft(['c2' => $tableName], new \Zend_Db_Expr('c2.path LIKE CONCAT(`c`.`path`,\'/%\')'), [])
            ->group('c.path');
        $expectedData = $this->connection->fetchAll($select);

        foreach ($expectedData as $row) {
            $data[$row['entity_id']] = [
                'children_count' => (int)$row['children_count'],
                'level' => (int)$row['level']
            ];
        }

        return $data;
    }

    /**
     * Get actual data of categories
     *
     * @param string $tableName Table name, processes by resource->getTable
     * @return array
     */
    protected function getActualCategoriesData($tableName)
    {
        $data = [];
        $select = $this->connection->select()
            ->from($tableName, ['entity_id', 'children_count', 'level']);
        $actualData = $this->connection->fetchAll($select);

        foreach ($actualData as $row) {
            $data[$row['entity_id']] = [
                'children_count' => (int)$row['children_count'],
                'level' => (int)$row['level']
            ];
        }

        return $data;
    }
}
