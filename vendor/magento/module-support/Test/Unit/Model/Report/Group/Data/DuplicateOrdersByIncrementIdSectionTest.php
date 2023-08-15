<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Data;

use Magento\Support\Model\Report\Group\Data\DuplicateOrdersByIncrementIdSection;

class DuplicateOrdersByIncrementIdSectionTest extends AbstractDataGroupTest
{
    /**
     * @var string
     */
    protected $reportNamespace = DuplicateOrdersByIncrementIdSection::class;

    /**
     * @return void
     */
    public function testGenerate()
    {
        $entityTable = 'sales_order';
        $storeTable = 'store';
        $incrementId = '00001';
        $entityId = 10;
        $storeId = 1;
        $customerId = 100;
        $createdAt = '21.09.2015 10:51';
        $storeName = 'Default';
        $whereString = '`e`.`increment_id` = "' . $incrementId . '"';

        $this->resourceMock->expects($this->any())
            ->method('getTable')
            ->willReturnMap([
                [$entityTable, $entityTable],
                [$storeTable, $storeTable]
            ]);
        $this->connectionMock->expects($this->once())
            ->method('quoteInto')
            ->with('`e`.`increment_id` = ?', $incrementId, null, null)
            ->willReturn($whereString);

        $sqlGetDuplicates = 'SELECT COUNT(1) AS `cnt`, `increment_id`'
            . ' FROM `' . $entityTable . '`'
            . ' GROUP BY `increment_id`'
            . ' HAVING `cnt` > 1'
            . ' ORDER BY `cnt` DESC, `entity_id`';
        $duplicates = [['cnt' => 2, 'increment_id' => $incrementId]];
        $sqlGetDuplicateInfo = 'SELECT `e`.`entity_id`, `e`.`store_id`, `e`.`customer_id`,'
            . ' `e`.`increment_id`, `e`.`created_at`, `s`.`name` AS `store_name`'
            . ' FROM `' . $entityTable . '` AS `e`'
            . ' LEFT JOIN `' . $storeTable . '` AS `s` USING(store_id)'
            . ' WHERE ' . $whereString;
        $duplicateInfo = [
            [
                'entity_id' => $entityId,
                'store_id' => $storeId,
                'customer_id' => $customerId,
                'increment_id' => $incrementId,
                'created_at' => $createdAt,
                'store_name' => $storeName
            ]
        ];
        $this->connectionMock->expects($this->any())
            ->method('fetchAll')
            ->willReturnMap([
                [$sqlGetDuplicates, [], null, $duplicates],
                [$sqlGetDuplicateInfo, [], null, $duplicateInfo]
            ]);

        $expectedResult = $this->getExpectedResult([
            [$entityId, $incrementId, $storeName . ' {ID:' . $storeId . '}', $createdAt, $customerId]
        ]);

        $this->assertEquals($expectedResult, $this->report->generate());
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getExpectedResult($data = [])
    {
        return [
            (string)__('Duplicate Orders By Increment Id') => [
                'headers' => [__('Id'), __('Increment Id'), __('Store'), __('Created At'), __('Customer Id')],
                'data' => $data
            ]
        ];
    }
}
