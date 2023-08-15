<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Observer;

use Magento\AdminGws\Model\ConfigInterface;
use Magento\AdminGws\Model\Role as AdminGwsRole;
use Magento\AdminGws\Observer\AdminControllerPredispatch;
use Magento\AdminGws\Observer\RolePermissionAssigner;
use Magento\Authorization\Model\Role as AuthRole;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\AdminGws\Observer\AdminControllerPredispatch
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AdminControllerPredispatchTest extends TestCase
{
    /**
     * @var AdminControllerPredispatch
     */
    private $adminControllerPredispatchObserver;

    /**
     * @var Session|MockObject
     */
    private $backendAuthSession;

    /**
     * @var User|MockObject
     */
    private $user;

    /**
     * @var AdminGwsRole|MockObject
     */
    private $role;

    /**
     * @var AuthRole|MockObject
     */
    private $authRole;

    /**
     * @var SystemStore|MockObject
     */
    private $systemStore;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var RolePermissionAssigner|MockObject
     */
    private $rolePermissionAssigner;

    /**
     * @var ConfigInterface|MockObject
     */
    private $config;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
        $this->role = $this->createMock(AdminGwsRole::class);
        $this->systemStore = $this->createMock(SystemStore::class);
        $this->storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->rolePermissionAssigner = $this->createMock(RolePermissionAssigner::class);
        $this->config = $this->getMockForAbstractClass(ConfigInterface::class);

        $this->backendAuthSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->addMethods(['getUser'])
            ->getMock();
        $this->backendAuthSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->backendAuthSession->expects($this->any())->method('getUser')->willReturn($this->user);

        $this->authRole = $this->getMockBuilder(AuthRole::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load'])
            ->addMethods(['getGwsDataIsset'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->adminControllerPredispatchObserver = $objectManagerHelper->getObject(
            AdminControllerPredispatch::class,
            [
                'role' => $this->role,
                'backendAuthSession' => $this->backendAuthSession,
                'systemStore' => $this->systemStore,
                'rolePermissionAssigner' => $this->rolePermissionAssigner,
                'config' => $this->config,
                'storeManager' => $this->storeManager
            ]
        );
    }

    /**
     * Test for execute with GwsDataPresent
     *
     * @return void
     */
    public function testAdminControllerPredispatchGwsDataPresent(): void
    {
        $this->authRole->expects($this->once())->method('getGwsDataIsset')->willReturn(true);
        $this->authRole->expects($this->never())->method('load');

        $this->user->expects($this->any())->method('getRole')->willReturn($this->authRole);
        $this->role->expects($this->any())->method('getIsAll')->willReturn(true);
        $this->role->expects($this->once())->method('setAdminRole')->with($this->authRole);

        /** @var Observer|MockObject $observer */
        $observer = $this->createMock(Observer::class);

        $this->adminControllerPredispatchObserver->execute($observer);
    }

    /**
     * Test for execute with GwsDataMissing
     *
     * @return void
     */
    public function testAdminControllerPredispatchGwsDataMissing(): void
    {
        $this->authRole->expects($this->once())->method('getGwsDataIsset')->willReturn(false);
        $this->authRole->expects($this->once())->method('load');

        $this->user->expects($this->any())->method('getRole')->willReturn($this->authRole);
        $this->role->expects($this->any())->method('getIsAll')->willReturn(true);
        $this->role->expects($this->once())->method('setAdminRole')->with($this->authRole);

        /** @var Observer|MockObject $observer */
        $observer = $this->createMock(Observer::class);

        $this->adminControllerPredispatchObserver->execute($observer);
    }

    /**
     * Test for execute with  limited user
     *
     * @return void
     */
    public function testAdminControllerPredispatchGwsIsNotAll(): void
    {
        $request = new DataObject();
        $action = 'adminhtml__index__realAction';
        $method = 'realMethod';
        $valueMap = [
            ['controller_predispatch', [$action => $method]],
        ];

        $this->authRole->method('getGwsDataIsset')->willReturn(true);
        $this->authRole->method('load');

        $this->user->expects($this->once())->method('getRole')->willReturn($this->authRole);
        $this->role->expects($this->any())->method('getIsAll')->willReturn(false);
        $this->role->expects($this->once())->method('setAdminRole')->with($this->authRole);
        $this->role->expects($this->once())->method('getWebsiteIds')->willReturn([]);

        $this->storeManager->expects($this->once())->method('setIsSingleStoreModeAllowed')->with(false);

        $this->rolePermissionAssigner
            ->method('denyAclLevelRules')
            ->withConsecutive(
                [$this->adminControllerPredispatchObserver::ACL_WEBSITE_LEVEL],
                [$this->adminControllerPredispatchObserver::ACL_STORE_LEVEL]
            );

        $this->systemStore->expects($this->once())->method('setIsAdminScopeAllowed')->with(false);
        $this->systemStore->expects($this->once())->method('reload');

        $this->config->expects($this->once())->method('getCallbacks')->willReturnMap($valueMap);

        /** @var  Event|MockObject $event */
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getRequest')->willReturn($request);

        /** @var Observer|MockObject $observer */
        $observer = $this->createMock(Observer::class);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);

        $this->adminControllerPredispatchObserver->execute($observer);
    }
}
