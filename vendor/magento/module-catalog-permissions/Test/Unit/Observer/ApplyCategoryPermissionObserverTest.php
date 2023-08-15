<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\Catalog\Model\Category;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\CatalogPermissions\Observer\ApplyCategoryPermissionObserver;
use Magento\CatalogPermissions\Observer\ApplyPermissionsOnCategory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyCategoryPermissionObserver
 */
class ApplyCategoryPermissionObserverTest extends TestCase
{
    /**
     * @var ApplyCategoryPermissionObserver
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

        $this->observer = new ApplyCategoryPermissionObserver(
            $this->permissionsConfig,
            $this->storeManager,
            $this->createMock(Session::class),
            $this->permissionIndex,
            $this->createMock(Data::class),
            $this->createMock(ApplyPermissionsOnCategory::class)
        );
    }

    /**
     * @return void
     */
    public function testApplyCategoryPermission()
    {
        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn(new DataObject(['website_id' => 123]));

        $categoryMock = $this->getMockBuilder(Category::class)
            ->setMethods(['getIsHidden', 'getId', 'setPermissions'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(33);
        $categoryMock
            ->expects($this->any())
            ->method('getIsHidden')
            ->willReturn(false);
        $categoryMock
            ->expects($this->once())
            ->method('setPermissions')
            ->with(1);

        $this->permissionIndex
            ->expects($this->any())
            ->method('getIndexForCategory')
            ->willReturn([33 => 1]);

        $responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->setMethods(['setRedirect', 'sendResponse'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(
                new DataObject(
                    [
                        'category' => $categoryMock,
                        'controller_action' => new DataObject(['response' => $responseMock])
                    ]
                )
            );

        $this->observer->execute($this->eventObserverMock);
    }

    /**
     * @return void
     */
    public function testApplyCategoryPermissionException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn(new DataObject(['website_id' => 123]));

        $categoryMock = $this->getMockBuilder(Category::class)
            ->setMethods(['getIsHidden', 'getId', 'setPermissions'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(33);
        $categoryMock
            ->expects($this->any())
            ->method('getIsHidden')
            ->willReturn(true);
        $categoryMock
            ->expects($this->once())
            ->method('setPermissions')
            ->with(1);

        $this->permissionIndex
            ->expects($this->any())
            ->method('getIndexForCategory')
            ->willReturn([33 => 1]);

        $responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->setMethods(['setRedirect', 'sendResponse'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $responseMock
            ->expects($this->once())
            ->method('setRedirect');

        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(
                new DataObject(
                    [
                        'category' => $categoryMock,
                        'controller_action' => new DataObject(['response' => $responseMock])
                    ]
                )
            );

        $this->observer->execute($this->eventObserverMock);
    }
}
