<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Category;

use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Indexer\Model\Indexer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModeSwitcherTest extends TestCase
{
    /**
     * @var TableMaintainer|MockObject
     */
    private $tableMaintainerMock;

    /**
     * @var Indexer|MockObject
     */
    private $indexerMock;

    /**
     * @var TypeListInterface|MockObject
     */
    private $cacheTypeListMock;

    /**
     * @var ConfigInterface|MockObject
     */
    private $configWriterMock;

    /**
     * @var ModeSwitcher
     */
    private $modeSwitcher;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tableMaintainerMock = $this->createMock(TableMaintainer::class);
        $this->indexerMock = $this->createMock(Indexer::class);
        $this->cacheTypeListMock = $this->getMockForAbstractClass(TypeListInterface::class);
        $this->configWriterMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $objectManager = new ObjectManager($this);
        $this->modeSwitcher = $objectManager->getObject(
            ModeSwitcher::class,
            [
                'tableMaintainer' => $this->tableMaintainerMock,
                'configWriter' => $this->configWriterMock,
                'cacheTypeList' => $this->cacheTypeListMock,
                'indexer' => $this->indexerMock
            ]
        );
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function testSwitchMode()
    {
        $this->tableMaintainerMock->expects($this->once())->method('createTablesForCurrentMode');
        $this->indexerMock->expects($this->exactly(2))->method('load');
        $this->indexerMock->expects($this->exactly(2))->method('invalidate');
        $this->cacheTypeListMock->expects($this->once())->method('cleanType');
        $this->configWriterMock->expects($this->once())->method('saveConfig');
        $this->modeSwitcher->switchMode(ModeSwitcher::DIMENSION_CUSTOMER_GROUP, ModeSwitcher::DIMENSION_NONE);
    }

    /**
     * @return void
     */
    public function testGetDimensionModes()
    {
        $dimensionModes[] = $this->modeSwitcher->getDimensionModes();
        $dimensions = $dimensionModes[0]->getDimensions();
        $this->assertArrayHasKey($this->modeSwitcher::DIMENSION_NONE, $dimensions);
        $this->assertArrayHasKey($this->modeSwitcher::DIMENSION_CUSTOMER_GROUP, $dimensions);
    }
}
