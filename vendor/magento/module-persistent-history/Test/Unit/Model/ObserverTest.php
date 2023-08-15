<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Model;

use Magento\Catalog\Block\Product\Compare\ListCompare;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Persistent\Helper\Session;
use Magento\PersistentHistory\Helper\Data;
use Magento\PersistentHistory\Model\Observer;
use Magento\Reports\Block\Product\Compared;
use Magento\Reports\Block\Product\Viewed;
use Magento\Reports\Model\Product\Index\AbstractIndex;
use Magento\Sales\Block\Reorder\Sidebar;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObserverTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $persistentHelperMock;

    /**
     * @var MockObject
     */
    protected $sessionHelperMock;

    /**
     * @var Observer
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);
        $this->persistentHelperMock = $this->createPartialMock(
            Data::class,
            [
                'isOrderedItemsPersist',
                'isViewedProductsPersist',
                'isComparedProductsPersist',
                'isCompareProductsPersist',
            ]
        );
        $this->sessionHelperMock = $this->createPartialMock(Session::class, ['getSession']);
        $this->subject = $objectManager->getObject(
            Observer::class,
            ['ePersistentData' => $this->persistentHelperMock, 'persistentSession' => $this->sessionHelperMock]
        );
    }

    public function testInitReorderSidebarIfOrderItemsNotPersist()
    {
        $blockMock = $this->createMock(Sidebar::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('isOrderedItemsPersist')
            ->willReturn(false);
        $this->subject->initReorderSidebar($blockMock);
    }

    public function testInitReorderSidebarSuccess()
    {
        $customerId = 100;
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->willReturn($this->getSessionMock());

        $blockMock = $this->getMockBuilder(Sidebar::class)
            ->addMethods(['setCustomerId', 'initOrders'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->persistentHelperMock->expects($this->once())
            ->method('isOrderedItemsPersist')
            ->willReturn(true);

        $blockMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)->willReturnSelf();
        $blockMock->expects($this->never())->method('initOrders')->willReturnSelf();
        $this->subject->initReorderSidebar($blockMock);
    }

    public function testEmulateViewedProductsIfProductsNotPersist()
    {
        $blockMock = $this->createMock(Viewed::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('isViewedProductsPersist')
            ->willReturn(false);
        $this->subject->emulateViewedProductsBlock($blockMock);
    }

    public function testEmulateViewedProductsSuccess()
    {
        $customerId = 100;
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->willReturn($this->getSessionMock());

        $blockMock = $this->getMockBuilder(Viewed::class)
            ->addMethods(['setCustomerId'])
            ->onlyMethods(['getModel'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->persistentHelperMock->expects($this->once())
            ->method('isViewedProductsPersist')
            ->willReturn(true);

        $modelMock = $this->getMockBuilder(AbstractIndex::class)
            ->addMethods(['setCustomerId'])
            ->onlyMethods(['calculate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $modelMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)->willReturnSelf();
        $modelMock->expects($this->once())->method('calculate')->willReturnSelf();

        $blockMock->expects($this->once())
            ->method('getModel')
            ->willReturn($modelMock);
        $blockMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)->willReturnSelf();

        $this->subject->emulateViewedProductsBlock($blockMock);
    }

    public function testEmulateComparedProductsIfProductsNotPersist()
    {
        $blockMock = $this->createMock(Compared::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('isComparedProductsPersist')
            ->willReturn(false);
        $this->subject->emulateComparedProductsBlock($blockMock);
    }

    public function testEmulateComparedProductsSuccess()
    {
        $customerId = 100;
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->willReturn($this->getSessionMock());

        $blockMock = $this->getMockBuilder(Compared::class)
            ->addMethods(['setCustomerId'])
            ->onlyMethods(['getModel'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->persistentHelperMock->expects($this->once())
            ->method('isComparedProductsPersist')
            ->willReturn(true);

        $modelMock = $this->getMockBuilder(AbstractIndex::class)
            ->addMethods(['setCustomerId'])
            ->onlyMethods(['calculate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $modelMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)->willReturnSelf();
        $modelMock->expects($this->once())->method('calculate')->willReturnSelf();

        $blockMock->expects($this->once())
            ->method('getModel')
            ->willReturn($modelMock);
        $blockMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)->willReturnSelf();

        $this->subject->emulateComparedProductsBlock($blockMock);
    }

    public function testEmulateCompareProductListIfProductsNotPersistent()
    {
        $blockMock = $this->createMock(ListCompare::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->willReturn(false);
        $this->subject->emulateCompareProductsListBlock($blockMock);
    }

    public function testEmulateCompareProductListSuccess()
    {
        $customerId = 100;
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->willReturn($this->getSessionMock());

        $blockMock = $this->createPartialMock(
            ListCompare::class,
            ['setCustomerId']
        );
        $this->persistentHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->willReturn(true);
        $blockMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)->willReturnSelf();
        $this->subject->emulateCompareProductsListBlock($blockMock);
    }

    protected function getSessionMock()
    {
        $customerId = 100;
        $sessionMock = $this->getMockBuilder(\Magento\Persistent\Model\Session::class)->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sessionMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        return $sessionMock;
    }
}
