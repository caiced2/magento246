<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Observer;

use Magento\AdminGws\Observer\AddDataAfterRoleLoad;
use Magento\AdminGws\Observer\RefreshRolePermissions;
use Magento\AdminGws\Observer\RolePermissionAssigner;
use Magento\Authorization\Model\Role;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddDataAfterRoleLoadTest extends TestCase
{
    /**
     * @var AddDataAfterRoleLoad
     */
    protected $_addDataAfterRoleLoadObserver;

    /**
     * @var RefreshRolePermissions|MockObject
     */
    protected $_rolePermissionAssigner;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->_rolePermissionAssigner = $this->getMockBuilder(
            RolePermissionAssigner::class
        )->setMethods(
            []
        )->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->_addDataAfterRoleLoadObserver = $objectManagerHelper->getObject(
            AddDataAfterRoleLoad::class,
            [
                $this->_rolePermissionAssigner
            ]
        );
    }

    public function testAddDataAfterRoleLoad()
    {
        /** @var Role|MockObject $role */
        $role = $this->createMock(Role::class);

        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getObject')->willReturn($role);
        $observer = $this->createMock(Observer::class);
        $observer->expects($this->once())->method('getEvent')->willReturn($event);

        $this->_addDataAfterRoleLoadObserver->execute($observer);
    }
}
