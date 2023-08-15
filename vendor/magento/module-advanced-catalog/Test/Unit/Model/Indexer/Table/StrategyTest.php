<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCatalog\Test\Unit\Model\Indexer\Table;

use Magento\AdvancedCatalog\Model\Indexer\Table\Strategy;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\TemporaryTableStrategy;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StrategyTest extends TestCase
{
    /**
     * Strategy object
     *
     * @var \Magento\AdvancedCatalog\Model\Indexer\Table\Strategy
     */
    protected $_model;

    /**
     * Resource mock
     *
     * @var ResourceConnection|MockObject
     */
    protected $_resourceMock;

    /**
     * Adapter mock
     *
     * @var Mysql|MockObject
     */
    protected $_adapterMock;

    /**
     * @var MockObject
     */
    private $strategyMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->_resourceMock = $this->createMock(ResourceConnection::class);
        $this->_adapterMock = $this->createMock(Mysql::class);
        $this->_resourceMock->expects($this->any())->method('getConnection')->willReturn($this->_adapterMock);
        $this->strategyMock = $this->createMock(
            TemporaryTableStrategy::class
        );

        $this->_model = new Strategy(
            $this->_resourceMock,
            $this->strategyMock
        );
    }

    /**
     * Test use idx table switcher
     *
     * @return void
     */
    public function testUseIdxTable()
    {
        $this->assertFalse($this->_model->getUseIdxTable());
        $this->_model->setUseIdxTable(false);
        $this->assertFalse($this->_model->getUseIdxTable());
        $this->_model->setUseIdxTable(true);
        $this->assertTrue($this->_model->getUseIdxTable());
        $this->_model->setUseIdxTable();
        $this->assertFalse($this->_model->getUseIdxTable());
    }

    /**
     * Test prepare table name with using idx table
     *
     * @return void
     */
    public function testPrepareTableNameUseIdxTable()
    {
        $this->strategyMock->expects($this->once())->method('prepareTableName')->with('test')->willReturn('test_idx');
        $this->assertEquals('test_idx', $this->_model->prepareTableName('test'));
    }
}
