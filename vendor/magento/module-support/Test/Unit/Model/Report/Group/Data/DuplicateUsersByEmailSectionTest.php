<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Data;

use Magento\Support\Model\Report\Group\Data\DuplicateUsersByEmailSection;

class DuplicateUsersByEmailSectionTest extends AbstractDataGroupTest
{
    /**
     * @var string
     */
    protected $reportNamespace = DuplicateUsersByEmailSection::class;

    /**
     * @return void
     */
    public function testGenerate()
    {
        $entityTable = 'customer_entity';
        $storeWebsiteTable = 'store_website';
        $duplicateEmail = 'user@example.com';
        $whereString = '`e`.`email` = "' . $duplicateEmail . '"';
        $entityId = 2;
        $websiteId = 1;
        $websiteName = 'Default website';
        $createdAt = '21.09.2015 12:02';

        $this->resourceMock->expects($this->any())
            ->method('getTable')
            ->willReturnMap([
                [$entityTable, $entityTable],
                [$storeWebsiteTable, $storeWebsiteTable]
            ]);
        $this->connectionMock->expects($this->once())
            ->method('quoteInto')
            ->with('`e`.`email` = ?', $duplicateEmail, null, null)
            ->willReturn($whereString);

        $sqlGetDuplicates = 'SELECT COUNT(1) AS `cnt`, `email`'
            . ' FROM `' . $entityTable . '`'
            . ' GROUP BY `email`'
            . ' HAVING `cnt` > 1'
            . ' ORDER BY `cnt` DESC, `entity_id`';
        $duplicates = [['cnt' => 2, 'email' => $duplicateEmail]];
        $sqlGetDuplicateInfo = 'SELECT `e`.`entity_id`, `e`.`email`, `e`.`website_id`, `e`.`created_at`,'
            . ' `w`.`name` as `website_name`'
            . ' FROM `' . $entityTable . '` AS `e`'
            . ' LEFT JOIN `' . $storeWebsiteTable . '` AS `w` USING(website_id)'
            . ' WHERE ' . $whereString;
        $duplicateInfo = [
            [
                'entity_id' => $entityId,
                'email' => $duplicateEmail,
                'website_id' => $websiteId,
                'created_at' => $createdAt,
                'website_name' => $websiteName
            ]
        ];
        $this->connectionMock->expects($this->any())
            ->method('fetchAll')
            ->willReturnMap([
                [$sqlGetDuplicates, [], null, $duplicates],
                [$sqlGetDuplicateInfo, [], null, $duplicateInfo]
            ]);

        $expectedResult = $this->getExpectedResult([
            [$entityId, $duplicateEmail, $websiteName . ' {ID:' . $websiteId . '}', $createdAt]
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
            (string)__('Duplicate Users By Email') => [
                'headers' => [__('Id'), __('Email'), __('Website'), __('Created At')],
                'data' => $data
            ]
        ];
    }
}
