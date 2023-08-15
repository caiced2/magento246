<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\CatalogPermissions\Observer\ApplyProductPermissionOnCollectionObserver;
use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyProductPermissionOnCollectionObserver
 */
class ApplyProductPermissionOnCollectionObserverTest extends TestCase
{
    /**
     * @var ApplyProductPermissionOnCollectionObserver
     */
    protected $observer;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $permissionsConfig;

    /**
     * @var Index|MockObject
     */
    protected $permissionIndex;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->permissionsConfig = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->permissionIndex = $this->createMock(Index::class);

        $this->eventObserverMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new ApplyProductPermissionOnCollectionObserver(
            $this->permissionsConfig,
            $this->createMock(Session::class),
            $this->permissionIndex
        );
    }

    /**
     * @return void
     */
    public function testApplyProductPermissionOnCollection()
    {
        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['collection' => [1, 2, 3]]));

        $this->permissionIndex
            ->expects($this->once())
            ->method('addIndexToProductCollection')
            ->with([1, 2, 3], $this->anything());

        $this->observer->execute($this->eventObserverMock);
    }
}
