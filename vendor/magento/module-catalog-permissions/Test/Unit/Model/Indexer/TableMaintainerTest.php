<?php
declare(strict_types=1);

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer;

use Magento\CatalogPermissions\Model\Indexer\AbstractAction;
use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TableMaintainerTest extends TestCase
{

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * @var GroupCollectionFactory|MockObject
     */
    private $groupCollectionFactory;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourceMock->method('getConnection')->willReturn($this->connectionMock);
        $this->groupCollectionFactory = $this->createMock(GroupCollectionFactory::class);
        $collectionMock = $this->createMock(Collection::class);
        $this->groupCollectionFactory->method('create')->willReturn($collectionMock);
        $collectionMock->method('getAllIds')->willReturn(['1']);
        $objectManager = new ObjectManager($this);
        $this->tableMaintainer = $objectManager->getObject(
            TableMaintainer::class,
            [
                'resource' => $this->resourceMock,
                'groupCollectionFactory' => $this->groupCollectionFactory,
                'scopeConfig' => $this->scopeConfigMock
            ]
        );
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testGetAllCategoryTablesForCustomerGroups($currentMode, $customerGroupId)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE)
            ->willReturn($currentMode);
        $this->resourceMock->expects($this->once())->method('getTableName')
            ->willReturn(AbstractAction::INDEX_TABLE);

        $this->assertEquals(
            [AbstractAction::INDEX_TABLE . $customerGroupId],
            $this->tableMaintainer->getAllCategoryTablesForCustomerGroups()
        );
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testGetAllProductsTablesForCustomerGroups($currentMode, $customerGroupId)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE)
            ->willReturn($currentMode);
        $this->resourceMock->expects($this->once())->method('getTableName')
            ->willReturn(AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX);
        $this->assertEquals(
            [AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX . $customerGroupId],
            $this->tableMaintainer->getAllProductsTablesForCustomerGroups()
        );
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testCreateTablesForCurrentMode($currentMode)
    {
        $this->connectionMock->expects($this->exactly(4))->method('createTable');
        $this->resourceMock->expects($this->exactly(8))->method('getTableName')
            ->willReturnOnConsecutiveCalls(
                AbstractAction::INDEX_TABLE,
                AbstractAction::INDEX_TABLE,
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX,
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX,
                AbstractAction::INDEX_TABLE,
                AbstractAction::INDEX_TABLE,
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX,
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
            );
        $this->connectionMock->expects($this->exactly(4))->method('isTableExists')->willReturn(false);
        $tableMock = $this->createMock(Table::class);
        $this->connectionMock->expects($this->exactly(4))->method('createTableByDdl')->willReturn($tableMock);
        $this->tableMaintainer->createTablesForCurrentMode($currentMode);
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testDropOldData($currentMode)
    {
        $this->resourceMock->expects($this->exactly(4))->method('getTableName')
            ->willReturnOnConsecutiveCalls(
                AbstractAction::INDEX_TABLE,
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX,
                AbstractAction::INDEX_TABLE,
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
            );
        $this->connectionMock->expects($this->exactly(4))->method('dropTable');
        $this->tableMaintainer->dropOldData($currentMode);
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testResolveMainTableNameCategory($currentMode, $customerGroupId)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE)
            ->willReturn($currentMode);
        $this->resourceMock->expects($this->once())->method('getTableName')
            ->willReturn(AbstractAction::INDEX_TABLE);

        $this->assertEquals(
            AbstractAction::INDEX_TABLE . $customerGroupId,
            $this->tableMaintainer->resolveMainTableNameCategory('1')
        );
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testResolveReplicaTableNameCategory($currentMode, $customerGroupId)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE)
            ->willReturn($currentMode);
        $this->resourceMock->expects($this->once())->method('getTableName')
            ->willReturn(AbstractAction::INDEX_TABLE);
        $this->assertEquals(
            AbstractAction::INDEX_TABLE . $customerGroupId . AbstractAction::REPLICA_SUFFIX,
            $this->tableMaintainer->resolveReplicaTableNameCategory('1')
        );
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testResolveMainTableNameProduct($currentMode, $customerGroupId)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE)
            ->willReturn($currentMode);
        $this->resourceMock->expects($this->once())->method('getTableName')
            ->willReturn(AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX);
        $this->assertEquals(
            AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX . $customerGroupId,
            $this->tableMaintainer->resolveMainTableNameProduct('1')
        );
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testResolveReplicaTableNameProduct($currentMode, $customerGroupId)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE)
            ->willReturn($currentMode);
        $this->resourceMock->expects($this->once())->method('getTableName')
            ->willReturn(AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX);
        $this->assertEquals(
            AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
            . $customerGroupId . AbstractAction::REPLICA_SUFFIX,
            $this->tableMaintainer->resolveReplicaTableNameProduct('1')
        );
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testClearIndexTempTable($currentMode)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE)
            ->willReturn($currentMode);
        $this->resourceMock->expects($this->exactly(2))->method('getTableName')
            ->willReturnOnConsecutiveCalls(
                AbstractAction::INDEX_TABLE,
                AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
            );
        $this->connectionMock->expects($this->exactly(2))->method('truncateTable');
        $this->tableMaintainer->clearIndexTempTable();
    }

    /**
     * @return void
     * @dataProvider getCurrentMode
     */
    public function testGetInitialSelect($currentMode)
    {
        $selectMock = $this->createMock(Select::class);
        $this->connectionMock->method('select')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('from');
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE)
            ->willReturn($currentMode);
        $this->tableMaintainer->getInitialSelect(TableMaintainer::CATEGORY, '1');
    }

    /**
     * @return array
     */
    public function getCurrentMode()
    {
        return [
            [
                'currentMode' => ModeSwitcher::DIMENSION_CUSTOMER_GROUP,
                'customerGroupId' => '_1'
            ],
            [
                'currentMode' => ModeSwitcher::DIMENSION_NONE,
                'customerGroupId' => ''
            ]
        ];
    }
}
