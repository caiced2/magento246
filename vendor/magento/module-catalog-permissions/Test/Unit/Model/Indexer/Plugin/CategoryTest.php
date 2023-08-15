<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

use Magento\CatalogPermissions\App\Backend\Config;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions\Row as PermissionsRow;
use Magento\CatalogPermissions\Model\Indexer\Plugin\Category;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Model\PermissionFactory;
use Magento\Framework\Authorization;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryTest extends TestCase
{
    /**
     * @var IndexerInterface|MockObject
     */
    protected $indexerMock;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $appConfigMock;

    /**
     * @var AuthorizationInterface|MockObject
     */
    protected $authorizationMock;

    /**
     * @var PermissionFactory|MockObject
     */
    protected $permissionFactoryMock;

    /**
     * @var Permission|MockObject
     */
    protected $permissionMock;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var IndexerRegistry|MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var int
     */
    protected $categoryId = 10;

    protected function setUp(): void
    {
        $this->indexerMock = $this->createPartialMock(
            Indexer::class,
            ['getId', 'load', 'isScheduled', 'reindexRow', 'reindexList']
        );

        $this->appConfigMock = $this->createPartialMock(
            Config::class,
            ['isEnabled']
        );

        $this->authorizationMock = $this->createPartialMock(Authorization::class, ['isAllowed']);

        $this->permissionFactoryMock = $this->createPartialMock(
            PermissionFactory::class,
            ['create']
        );

        $this->permissionMock = $this->getMockBuilder(Permission::class)
            ->addMethods(['setCategoryId'])
            ->onlyMethods(['load', 'getId', 'delete', 'addData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexerRegistryMock = $this->createPartialMock(
            IndexerRegistry::class,
            ['get']
        );

        $this->category = new Category(
            $this->indexerRegistryMock,
            $this->appConfigMock,
            $this->authorizationMock,
            $this->permissionFactoryMock
        );
    }

    public function testAfterSaveNotAllowed()
    {
        $this->appConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->authorizationMock->expects(
            $this->once()
        )->method(
            'isAllowed'
        )->with(
            'Magento_CatalogPermissions::catalog_magento_catalogpermissions'
        )->willReturn(
            false
        );

        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->never())->method('hasData');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->willReturn($this->indexerMock);
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(false);

        $this->indexerMock->expects(
            $this->once()
        )->method(
            'reindexRow'
        )->with(
            $this->categoryId
        )->willReturnSelf();

        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->category->afterSave($categoryMock);
    }

    public function testAfterSaveAllowed()
    {
        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->once())->method('hasData')->with('permissions')->willReturn(true);

        $categoryMock->expects(
            $this->exactly(2)
        )->method(
            'getData'
        )->with(
            'permissions'
        )->willReturn(
            $this->getPermissionData(0)
        );

        $this->appConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->authorizationMock->expects(
            $this->once()
        )->method(
            'isAllowed'
        )->with(
            'Magento_CatalogPermissions::catalog_magento_catalogpermissions'
        )->willReturn(
            true
        );

        $this->permissionMock->expects($this->once())->method('load')->with(1)->willReturnSelf();
        $this->permissionMock->expects($this->once())->method('addData')->willReturnSelf();
        $this->permissionMock->expects($this->once())->method('setCategoryId')->willReturnSelf();
        $this->permissionMock->expects($this->once())->method('save')->willReturnSelf();

        $this->permissionFactoryMock->expects(
            $this->once()
        )->method(
            'create'
        )->willReturn(
            $this->permissionMock
        );

        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->category->afterSave($categoryMock);
    }

    public function testAfterSaveAllowedWithoutLoad()
    {
        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->once())->method('hasData')->with('permissions')->willReturn(true);

        $categoryMock->expects(
            $this->exactly(2)
        )->method(
            'getData'
        )->with(
            'permissions'
        )->willReturn(
            $this->getPermissionData(1)
        );

        $this->appConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->authorizationMock->expects(
            $this->once()
        )->method(
            'isAllowed'
        )->with(
            'Magento_CatalogPermissions::catalog_magento_catalogpermissions'
        )->willReturn(
            true
        );

        $this->permissionMock->expects($this->never())->method('load');
        $this->permissionMock->expects($this->once())->method('addData')->willReturnSelf();
        $this->permissionMock->expects($this->once())->method('setCategoryId')->willReturnSelf();
        $this->permissionMock->expects($this->once())->method('save')->willReturnSelf();

        $this->permissionFactoryMock->expects(
            $this->once()
        )->method(
            'create'
        )->willReturn(
            $this->permissionMock
        );

        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->category->afterSave($categoryMock);
    }

    public function testAfterSaveAllowedDeletePermission()
    {
        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->once())->method('hasData')->with('permissions')->willReturn(true);

        $categoryMock->expects(
            $this->exactly(2)
        )->method(
            'getData'
        )->with(
            'permissions'
        )->willReturn(
            $this->getPermissionData(2)
        );

        $this->appConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->authorizationMock->expects(
            $this->once()
        )->method(
            'isAllowed'
        )->with(
            'Magento_CatalogPermissions::catalog_magento_catalogpermissions'
        )->willReturn(
            true
        );

        $this->permissionMock->expects($this->once())->method('load')->with(1)->willReturnSelf();
        $this->permissionMock->expects($this->once())->method('getId')->willReturn(1);
        $this->permissionMock->expects($this->once())->method('delete');

        $this->permissionMock->expects($this->never())->method('addData')->willReturnSelf();

        $this->permissionFactoryMock->expects(
            $this->once()
        )->method(
            'create'
        )->willReturn(
            $this->permissionMock
        );

        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->willReturn($this->indexerMock);

        $this->category->afterSave($categoryMock);
    }

    protected function getPermissionData($index)
    {
        $data = [
            [
                [
                    'id' => 1,
                    'website_id' => PermissionsRow::FORM_SELECT_ALL_VALUES,
                    'customer_group_id' => PermissionsRow::FORM_SELECT_ALL_VALUES,
                ],
            ],
            [['website_id' => 1, 'customer_group_id' => PermissionsRow::FORM_SELECT_ALL_VALUES]],
            [['id' => 1, '_deleted' => true]],
        ];

        return $data[$index];
    }

    public function testAroundMove()
    {
        $parentId = 15;
        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->once())->method('getParentId')->willReturn($parentId);
        $closure = function () {
            return 'Expected';
        };
        $this->appConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->willReturn($this->indexerMock);
        $this->indexerMock->expects($this->once())->method('isScheduled')->willReturn(false);
        $this->indexerMock->expects($this->once())->method('reindexList')->with([$this->categoryId, $parentId]);

        $this->category->aroundMove($categoryMock, $closure, 0, 0);
    }

    /**
     * @return MockObject|\Magento\Catalog\Model\Category
     */
    protected function getCategory()
    {
        $categoryMock = $this->createPartialMock(
            \Magento\Catalog\Model\Category::class,
            ['hasData', 'getData', 'getId', 'getParentId']
        );
        $categoryMock->expects($this->any())->method('getId')->willReturn($this->categoryId);
        return $categoryMock;
    }
}
