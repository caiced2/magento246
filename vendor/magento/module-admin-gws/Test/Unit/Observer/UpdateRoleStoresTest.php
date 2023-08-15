<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Observer;

use Magento\AdminGws\Model\Role;
use Magento\AdminGws\Observer\UpdateRoleStores;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class UpdateRoleStoresTest extends TestCase
{
    /**
     * @var UpdateRoleStores
     */
    protected $_updateRoleStoresObserver;

    /**
     * @var Role
     */
    protected $_role;

    /**
     * @var DataObject
     */
    protected $_store;

    /**
     * @var Observer
     */
    protected $_observer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->_store = new DataObject();

        $this->_role = $this->getMockBuilder(
            Role::class
        )->setMethods(
            ['getStoreIds', 'setStoreIds']
        )->disableOriginalConstructor()
            ->getMock();

        $this->_observer = $this->getMockBuilder(
            Observer::class
        )->setMethods(
            ['getStore']
        )->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->_updateRoleStoresObserver = $objectManagerHelper->getObject(
            UpdateRoleStores::class,
            [
                'role' => $this->_role,
            ]
        );
    }

    public function testUpdateRoleStores()
    {
        $this->_store->setData('store_id', 1000);
        $this->_role->expects($this->any())->method('getStoreIds')->willReturn([1, 2, 3, 4, 5]);
        $this->_observer->expects($this->any())->method('getStore')->willReturn($this->_store);
        $this->_role->expects($this->once())->method('setStoreIds')->with($this->containsEqual(1000));
        $this->_updateRoleStoresObserver->execute($this->_observer);
    }
}
