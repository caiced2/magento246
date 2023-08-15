<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model\ResourceModel\Plugin;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Model\ResourceModel\Grid as GridResource;
use Magento\Sales\Model\ResourceModel\GridPool as GridPoolResource;
use Magento\SalesArchive\Model\ResourceModel\Archive as ArchiveResource;
use Magento\SalesArchive\Model\ResourceModel\Plugin\Grid as GridResourcePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GridTest extends TestCase
{
    /**
     * @var GridResourcePlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var GridPoolResource|MockObject
     */
    private $gridPoolResourceMock;

    /**
     * @var ArchiveResource|MockObject
     */
    private $archiveResourceMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var GridResource|MockObject
     */
    private $subjectMock;

    protected function setUp(): void
    {
        $this->gridPoolResourceMock = $this->getMockBuilder(GridPoolResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->archiveResourceMock = $this->getMockBuilder(ArchiveResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(GridResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            GridResourcePlugin::class,
            [
                'gridPoolResource' => $this->gridPoolResourceMock,
                'archiveResource' => $this->archiveResourceMock,
                'resourceConnection' => $this->resourceConnectionMock
            ]
        );
    }

    public function testBeforeRefresh()
    {
        $table = 'sales_order_table';
        $value = 'some_value';

        $this->subjectMock->expects(static::atLeastOnce())
            ->method('getGridTable')
            ->willReturn($table);
        $this->resourceConnectionMock->expects(static::atLeastOnce())
            ->method('getTableName')
            ->with('sales_order', ResourceConnection::DEFAULT_CONNECTION)
            ->willReturn($table);
        $this->archiveResourceMock->expects(static::atLeastOnce())
            ->method('isOrderInArchive')
            ->with($value)
            ->willReturn(true);
        $this->archiveResourceMock->expects(static::once())
            ->method('removeOrdersFromArchiveById')
            ->with([$value])
            ->willReturnArgument(0);
        $this->gridPoolResourceMock->expects(static::once())
            ->method('refreshByOrderId')
            ->with($value)
            ->willReturnSelf();

        $this->plugin->beforeRefresh($this->subjectMock, $value);
    }

    public function testBeforeRefreshWrongTable()
    {
        $value = 'some_value';

        $this->subjectMock->expects(static::atLeastOnce())
            ->method('getGridTable')
            ->willReturn('catalog_table');
        $this->resourceConnectionMock->expects(static::atLeastOnce())
            ->method('getTableName')
            ->with('sales_order', ResourceConnection::DEFAULT_CONNECTION)
            ->willReturn('sales_order_table');
        $this->archiveResourceMock->expects(static::any())
            ->method('isOrderInArchive')
            ->with($value)
            ->willReturn(true);
        $this->archiveResourceMock->expects(static::never())
            ->method('removeOrdersFromArchiveById');
        $this->gridPoolResourceMock->expects(static::never())
            ->method('refreshByOrderId');

        $this->plugin->beforeRefresh($this->subjectMock, $value);
    }

    public function testBeforeRefreshNotInArchive()
    {
        $table = 'sales_order_table';
        $value = 'some_value';

        $this->subjectMock->expects(static::any())
            ->method('getGridTable')
            ->willReturn($table);
        $this->resourceConnectionMock->expects(static::any())
            ->method('getTableName')
            ->with('sales_order', ResourceConnection::DEFAULT_CONNECTION)
            ->willReturn($table);
        $this->archiveResourceMock->expects(static::atLeastOnce())
            ->method('isOrderInArchive')
            ->with($value)
            ->willReturn(false);
        $this->archiveResourceMock->expects(static::never())
            ->method('removeOrdersFromArchiveById');
        $this->gridPoolResourceMock->expects(static::never())
            ->method('refreshByOrderId');

        $this->plugin->beforeRefresh($this->subjectMock, $value);
    }
}
