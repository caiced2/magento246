<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Observer;

use Magento\Authorization\Model\Role;
use Magento\AdminGws\Observer\RolePermissionAssigner;
use Magento\Store\Model\Group;
use Magento\Store\Model\ResourceModel\Group\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\AdminGws\Model\ConfigInterface;
use Magento\Framework\Acl\Builder;
use Magento\AdminGws\Model\CallbackInvoker;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RolePermissionAssignerTest extends TestCase
{
    /**
     * @var RolePermissionAssigner
     */
    private $rolePermissionAssigner;

    /**
     * @var Role|MockObject
     */
    private $role;

    /**
     * @var CollectionFactory|MockObject
     */
    private $storeGroupsFactory;

    /**
     * @var Group|MockObject
     */
    private $storeGroup;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Session|MockObject
     */
    private $backendAuthSession;

    /**
     * @var ConfigInterface|MockObject
     */
    private $config;

    /**
     * @var Builder|MockObject
     */
    private $aclBuilder;

    /**
     * @var CallbackInvoker|MockObject
     */
    private $callbackInvoker;

    /**
     * @var Website|MockObject
     */
    private $website;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->role = $this->getMockBuilder(
            Role::class
        )->onlyMethods(
            [
                'getData'
            ]
        )->addMethods(
            [
                'setGwsIsAll',
                'getGwsWebsites',
                'getGwsStoreGroups',
                'setGwsWebsites',
                'setGwsStoreGroups',
                'setGwsStores',
                'setGwsRelevantWebsites',
                'setGwsDataIsset'
            ]
        )->disableOriginalConstructor()
        ->getMock();

        $this->storeGroupsFactory = $this->getMockBuilder(
            CollectionFactory::class
        )->onlyMethods(
            ['create',]
        )->disableOriginalConstructor()
        ->getMock();

        $this->storeGroup = $this->getMockBuilder(
            Group::class
        )->onlyMethods(
            ['getWebsiteId', 'getId', 'getWebsite',]
        )->disableOriginalConstructor()
        ->getMock();

        $this->storeManager = $this->getMockBuilder(
            StoreManagerInterface::class
        )->onlyMethods(
            ['getStores',]
        )->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->backendAuthSession = $this->getMockBuilder(
            Session::class
        )->disableOriginalConstructor()
        ->getMock();

        $this->config = $this->getMockBuilder(
            ConfigInterface::class
        )->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->aclBuilder = $this->getMockBuilder(
            Builder::class
        )->disableOriginalConstructor()
        ->getMock();

        $this->callbackInvoker = $this->getMockBuilder(
            CallbackInvoker::class
        )->disableOriginalConstructor()
        ->getMock();

        $this->website = $this->getMockBuilder(
            Website::class
        )->onlyMethods(
            ['getId',]
        )->disableOriginalConstructor()
        ->getMock();

        $this->rolePermissionAssigner = new RolePermissionAssigner(
            $this->role,
            $this->storeGroupsFactory,
            $this->storeManager,
            $this->backendAuthSession,
            $this->config,
            $this->aclBuilder,
            $this->callbackInvoker,
        );
    }

    public function testAssignRolePermissions()
    {
        $this->role->expects($this->any())->method('getData')->with('gws_is_all')->willReturn(false);
        $this->role->expects($this->any())->method('setGwsIsAll')->with(false)->willReturnSelf();
        $this->role->expects($this->any())->method('getGwsWebsites')->willReturn(1);
        $this->role->expects($this->any())->method('setGwsWebsites')->with([1])->willReturnSelf();
        $this->storeGroupsFactory->expects($this->any())->method('create')->willReturn([$this->storeGroup]);
        $this->storeGroup->expects($this->any())->method('getWebsiteId')->willReturn(null);
        $this->storeGroup->expects($this->any())->method('getId')->willReturn(1);
        $this->storeGroup->expects($this->any())->method('getWebsite')->willReturn($this->website);
        $this->website->expects($this->any())->method('getId')->willReturn(1);
        $this->role->expects($this->any())->method('setGwsStoreGroups')->with([1])->willReturnSelf();
        $this->role->expects($this->any())->method('getGwsStoreGroups')->with()->willReturn([1]);
        $this->storeManager->expects($this->any())->method('getStores')->willReturn([]);
        $this->role->expects($this->any())->method('setGwsStores')->with([])->willReturnSelf();
        $this->role->expects($this->any())->method('setGwsRelevantWebsites')->with([1])->willReturnSelf();
        $this->role->expects($this->any())->method('setGwsDataIsset')->with(true)->willReturnSelf();
        $this->rolePermissionAssigner->assignRolePermissions($this->role);
    }
}
