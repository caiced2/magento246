<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScalableOms\Test\Unit\Console\Command;

use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ScalableOms\Console\Command\SplitSales;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for SplitSales
 */
class SplitSalesTest extends TestCase
{
    /**
     * @var SplitSales|MockObject
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->model = $objectManager->getObject(
            SplitSales::class,
            []
        );
    }

    /**
     * Test that method check existing table with correct connection name
     *
     * @return void
     */
    public function testMoveTable(): void
    {
        $tableName = 'sales_order_status';
        $secondSchemaName = 'magento_sales';

        $firstConnection = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock = $this->getMockBuilder(Select::class)
            ->addMethods(['fetchAll'])
            ->onlyMethods(['from'])
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->method('from')
            ->with($tableName)
            ->willReturnSelf();
        $selectMock->method('fetchAll')
            ->willReturn([]);
        $firstConnection->method('select')
            ->willReturn($selectMock);
        $firstConnection->method('getCreateTable')
            ->willReturn('create_table');
        $firstConnection->method('query')
            ->with($selectMock)
            ->willReturn($selectMock);

        $secondConnection = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $secondConnection->method('getConfig')
            ->willReturn(['dbname' => $secondSchemaName]);
        $secondConnection->expects($this->once())
            ->method('isTableExists')
            ->with($tableName, $secondSchemaName)
            ->willReturn(false);
        $secondConnection->expects($this->once())
            ->method('query')
            ->with('create_table')
            ->willReturnSelf();

        $moveTableMethod = new \ReflectionMethod(
            SplitSales::class,
            'moveTable'
        );
        $moveTableMethod->setAccessible(true);
        $moveTableMethod->invoke($this->model, $firstConnection, $secondConnection, $tableName);
    }
}
