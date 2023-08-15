<?php
declare(strict_types=1);

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Test\Unit\Plugin\Customer\Model\ResourceModel;

use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\CatalogPermissions\Plugin\Customer\Model\ResourceModel\GroupRepositoryPlugin;
use Magento\Customer\Model\Data\Group;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupRepositoryPluginTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var AdapterInterface
     */
    private $connectionMock;

    /**
     * @var GroupCollectionFactory|MockObject
     */
    private $groupCollectionFactoryMock;

    /**
     * @var GroupRepository
     */
    private $subject;

    /**
     * @var GroupRepositoryPlugin
     */
    private $groupRepositoryPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE)
            ->willReturn(ModeSwitcher::DIMENSION_CUSTOMER_GROUP);
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourceMock->expects($this->once())->method('getConnection')->willReturn($this->connectionMock);
        $this->subject = $this->createMock(GroupRepository::class);
        $this->groupCollectionFactoryMock = $this->createMock(GroupCollectionFactory::class);
        $collectionMock = $this->createMock(Collection::class);
        $this->groupCollectionFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->method('getAllIds')->willReturn(['0','1','2','3']);
        $objectManager = new ObjectManager($this);
        $this->groupRepositoryPlugin = $objectManager->getObject(
            GroupRepositoryPlugin::class,
            [
                'resource' => $this->resourceMock,
                'scopeConfig' => $this->scopeConfigMock,
                'groupCollectionFactory' => $this->groupCollectionFactoryMock
            ]
        );
    }

    public function testAfterSave()
    {
        $resultMock = $this->createMock(Group::class);
        $resultMock->expects($this->once())->method('getId')->willReturn('1');
        $tableMock = $this->createMock(Table::class);
        $this->connectionMock->expects($this->exactly(4))->method('createTableByDdl')->willReturn($tableMock);
        $this->groupRepositoryPlugin->afterSave($this->subject, $resultMock);
    }

    public function testAfterDeleteById()
    {
        $id = '1';
        $this->connectionMock->expects($this->exactly(4))->method('dropTable');
        $this->groupRepositoryPlugin->afterDeleteById($this->subject, true, $id);
    }
}
