<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Observer;

use Magento\AdminGws\Observer\RefreshRolePermissions;
use Magento\AdminGws\Observer\RolePermissionAssigner;
use Magento\Authorization\Model\Role;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RefreshRolePermissionsTest extends TestCase
{
    /**
     * @var RefreshRolePermissions
     */
    protected $_refreshRolePermissionsObserver;

    /**
     * @var Session|MockObject
     */
    protected $_backendAuthSession;

    /**
     * @var RefreshRolePermissions|MockObject
     */
    protected $_rolePermissionAssigner;

    /**
     * @var Observer
     */
    protected $_observer;

    /**
     * @var DataObject
     */
    protected $_store;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->_backendAuthSession = $this->getMockBuilder(Session::class)
            ->addMethods(['getUser'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_store = new DataObject();

        $this->_observer = $this->getMockBuilder(
            Observer::class
        )->setMethods(
            ['getStore']
        )->disableOriginalConstructor()
            ->getMock();
        $this->_observer->expects($this->any())->method('getStore')->willReturn($this->_store);

        $this->_rolePermissionAssigner = $this->getMockBuilder(
            RolePermissionAssigner::class
        )->setMethods(
            []
        )->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->_refreshRolePermissionsObserver = $objectManagerHelper->getObject(
            RefreshRolePermissions::class,
            [
                'rolePermissionAssigner' => $this->_rolePermissionAssigner,
                'backendAuthSession' => $this->_backendAuthSession
            ]
        );
    }

    public function testRefreshRolePermissions()
    {
        /** @var Role|MockObject $role */
        $role = $this->createMock(Role::class);

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getRole')->willReturn($role);

        $this->_backendAuthSession->expects($this->once())->method('getUser')->willReturn($user);

        $this->_refreshRolePermissionsObserver->execute($this->_observer);
    }

    public function testRefreshRolePermissionsInvalidUser()
    {
        $user = $this->getMockBuilder(\stdClass::class)->addMethods(['getRole'])
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->never())->method('getRole');

        $this->_backendAuthSession->expects($this->once())->method('getUser')->willReturn($user);

        $this->_refreshRolePermissionsObserver->execute($this->_observer);
    }
}
