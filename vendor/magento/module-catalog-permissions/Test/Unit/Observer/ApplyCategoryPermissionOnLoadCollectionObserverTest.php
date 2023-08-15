<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\CatalogPermissions\Observer\ApplyCategoryPermissionOnLoadCollectionObserver;
use Magento\CatalogPermissions\Observer\ApplyPermissionsOnCategory;
use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyCategoryPermissionOnLoadCollectionObserver
 */
class ApplyCategoryPermissionOnLoadCollectionObserverTest extends TestCase
{
    /**
     * @var ApplyCategoryPermissionOnLoadCollectionObserver
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
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->permissionsConfig = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->permissionIndex = $this->createMock(Index::class);

        $this->eventObserverMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new ApplyCategoryPermissionOnLoadCollectionObserver(
            $this->permissionsConfig,
            $this->storeManager,
            $this->createMock(Session::class),
            $this->permissionIndex,
            $this->createMock(ApplyPermissionsOnCategory::class)
        );
    }

    /**
     * @return void
     */
    public function testApplyCategoryPermissionOnLoadCollection()
    {
        $categoryCollection = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getColumnValues', 'getCategoryCollection', 'getItemById'])
            ->getMock();

        $categoryCollection
            ->expects($this->atLeastOnce())
            ->method('getColumnValues')
            ->with('entity_id')
            ->willReturn([1, 2, 3]);
        $categoryCollection
            ->expects($this->once())
            ->method('getItemById')
            ->with(123)
            ->willReturn(new DataObject());

        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn(new DataObject(['website_id' => 123]));

        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['category_collection' => $categoryCollection]));

        $this->permissionIndex
            ->expects($this->once())
            ->method('getIndexForCategory')
            ->with([1, 2, 3], $this->anything(), $this->anything())
            ->willReturn([123 => 987]);

        $this->observer->execute($this->eventObserverMock);
    }
}
