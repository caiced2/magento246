<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Observer;

use Magento\Catalog\Model\Product\Compare\Item;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PersistentHistory\Helper\Data;
use Magento\PersistentHistory\Observer\ExpireSidebarsObserver;
use Magento\Reports\Model\Product\Index\Compared;
use Magento\Reports\Model\Product\Index\ComparedFactory;
use Magento\Reports\Model\Product\Index\Viewed;
use Magento\Reports\Model\Product\Index\ViewedFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExpireSidebarsObserverTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $historyHelperMock;

    /**
     * @var MockObject
     */
    protected $compareItemMock;

    /**
     * @var MockObject
     */
    protected $comparedFactoryMock;

    /**
     * @var MockObject
     */
    protected $viewedFactoryMock;

    /**
     * @var ExpireSidebarsObserver
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->historyHelperMock = $this->createPartialMock(
            Data::class,
            ['isCompareProductsPersist', 'isComparedProductsPersist']
        );
        $this->compareItemMock = $this->createMock(Item::class);
        $this->comparedFactoryMock = $this->createPartialMock(
            ComparedFactory::class,
            ['create']
        );
        $this->viewedFactoryMock = $this->createPartialMock(
            ViewedFactory::class,
            ['create']
        );

        $this->subject = $objectManager->getObject(
            ExpireSidebarsObserver::class,
            [
                'ePersistentData' => $this->historyHelperMock,
                'compareItem' => $this->compareItemMock,
                'comparedFactory' => $this->comparedFactoryMock,
                'viewedFactory' => $this->viewedFactoryMock
            ]
        );
    }

    public function testSidebarExpireDataIfCompareProductsNotPersistAndComparedProductsNotPersist()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->historyHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->willReturn(false);
        $this->historyHelperMock->expects($this->exactly(2))
            ->method('isComparedProductsPersist')
            ->willReturn(false);
        $this->subject->execute($observerMock);
    }

    public function testSidebarExpireDataIfComparedProductsNotPersist()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->historyHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->willReturn(true);

        $this->compareItemMock->expects($this->once())->method('bindCustomerLogout')->willReturnSelf();

        $this->historyHelperMock->expects($this->exactly(2))
            ->method('isComparedProductsPersist')
            ->willReturn(false);
        $this->subject->execute($observerMock);
    }

    public function testSidebarExpireDataIfCompareProductsNotPersist()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->historyHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->willReturn(false);

        $this->historyHelperMock->expects($this->exactly(2))
            ->method('isComparedProductsPersist')
            ->willReturn(true);

        $comparedMock = $this->createMock(Compared::class);
        $comparedMock->expects($this->once())->method('purgeVisitorByCustomer')->willReturnSelf();
        $comparedMock->expects($this->once())->method('calculate')->willReturnSelf();
        $this->comparedFactoryMock->expects($this->once())->method('create')->willReturn($comparedMock);

        $viewedMock = $this->createMock(Viewed::class);
        $viewedMock->expects($this->once())->method('purgeVisitorByCustomer')->willReturnSelf();
        $viewedMock->expects($this->once())->method('calculate')->willReturnSelf();
        $this->viewedFactoryMock->expects($this->once())->method('create')->willReturn($viewedMock);

        $this->subject->execute($observerMock);
    }

    public function testSidebarExpireDataSuccess()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->historyHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->willReturn(true);

        $this->compareItemMock->expects($this->once())->method('bindCustomerLogout')->willReturnSelf();

        $this->historyHelperMock->expects($this->exactly(2))
            ->method('isComparedProductsPersist')
            ->willReturn(true);

        $comparedMock = $this->createMock(Compared::class);
        $comparedMock->expects($this->once())->method('purgeVisitorByCustomer')->willReturnSelf();
        $comparedMock->expects($this->once())->method('calculate')->willReturnSelf();
        $this->comparedFactoryMock->expects($this->once())->method('create')->willReturn($comparedMock);

        $viewedMock = $this->createMock(Viewed::class);
        $viewedMock->expects($this->once())->method('purgeVisitorByCustomer')->willReturnSelf();
        $viewedMock->expects($this->once())->method('calculate')->willReturnSelf();
        $this->viewedFactoryMock->expects($this->once())->method('create')->willReturn($viewedMock);

        $this->subject->execute($observerMock);
    }
}
