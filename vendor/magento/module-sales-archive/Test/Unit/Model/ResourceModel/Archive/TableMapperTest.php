<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model\ResourceModel\Archive;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesArchive\Model\ResourceModel\Archive\TableMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests \Magento\SalesArchive\Model\ResourceModel\Archive\TableMapper
 */
class TableMapperTest extends TestCase
{
    /**
     * @var TableMapper
     */
    private $tableMapper;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resources;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resources = $this->createPartialMock(
            ResourceConnection::class,
            ['getTableName']
        );

        $contextMock = $this->createPartialMock(
            Context::class,
            ['getResources']
        );
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->resources);

        $objectManager = new ObjectManager($this);
        $this->tableMapper = $objectManager->getObject(
            TableMapper::class,
            [
                'context' => $contextMock,
            ]
        );
    }

    /**
     * @dataProvider getArchiveEntityTableBySourceTableDataProvider
     *
     * @param string $expectedTableName
     * @return void
     */
    public function testGetArchiveEntityTableBySourceTable(string $expectedTableName)
    {
        $sourceEntityTable = 'sales_order_grid';
        $archiveTableName = 'magento_sales_order_grid_archive';

        $this->resources->expects($this->once())->method('getTableName')
            ->with($archiveTableName, 'default')->willReturn($expectedTableName);

        $this->assertEquals(
            $expectedTableName,
            $this->tableMapper->getArchiveEntityTableBySourceTable($sourceEntityTable)
        );
    }

    /**
     * @return array
     */
    public function getArchiveEntityTableBySourceTableDataProvider(): array
    {
        return [
            ['magento_sales_order_grid_archive'],
            ['testmagento_sales_order_grid_archive']
        ];
    }
}
