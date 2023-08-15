<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Plugin;

use Magento\Sales\Model\ResourceModel\Provider\Query\IdListBuilder;
use Magento\SalesArchive\Model\Config;
use Magento\SalesArchive\Model\ResourceModel\Archive\TableMapper;
use Magento\SalesArchive\Plugin\ArchivedEntitiesProcessorPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchivedEntitiesProcessorPluginTest extends TestCase
{
    /**
     * @var ArchivedEntitiesProcessorPlugin
     */
    private $plugin;

    /**
     * @var MockObject
     */
    private $tableMapperMock;

    /**
     * @var MockObject
     */
    private $configMock;

    protected function setUp(): void
    {
        $this->tableMapperMock = $this->getMockBuilder(TableMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->createMock(Config::class);
        $this->plugin = new ArchivedEntitiesProcessorPlugin(
            $this->tableMapperMock,
            $this->configMock
        );
    }

    public function testAfterGetIds()
    {
        $mainTableName = 'sales_order';
        $gridTableName = 'sales_order_grid';
        $archiveGridTableName = 'sales_order_archive_grid';
        $this->configMock
            ->expects($this->once())
            ->method('isArchiveActive')
            ->willReturn(true);
        $this->tableMapperMock
            ->expects($this::once())
            ->method('getArchiveEntityTableBySourceTable')
            ->willReturn($archiveGridTableName);
        $idListBuilder = $this->getMockBuilder(IdListBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $idListBuilder->expects($this::once())
            ->method('addAdditionalGridTable')
            ->with($archiveGridTableName);

        $this->assertEquals(
            [$mainTableName, $gridTableName],
            $this->plugin->beforeBuild($idListBuilder, $mainTableName, $gridTableName)
        );
    }
}
