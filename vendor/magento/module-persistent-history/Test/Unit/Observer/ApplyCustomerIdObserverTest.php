<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Observer;

use Magento\Framework\App\Test\Unit\Action\Stub\ActionStub;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Persistent\Helper\Session;
use Magento\PersistentHistory\Helper\Data;
use Magento\PersistentHistory\Observer\ApplyCustomerIdObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplyCustomerIdObserverTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $historyHelperMoc;

    /**
     * @var MockObject
     */
    protected $persistentHelperMock;

    /**
     * @var MockObject
     */
    protected $sessionHelperMock;

    /**
     * @var ApplyCustomerIdObserver
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager */
        $objectManager = new ObjectManager($this);

        $this->historyHelperMoc = $this->createMock(Data::class);
        $this->sessionHelperMock = $this->createMock(Session::class);
        $this->persistentHelperMock = $this->getMockBuilder(\Magento\Persistent\Helper\Data::class)->addMethods(
            ['isCompareProductsPersist']
        )
            ->onlyMethods(['canProcess'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $objectManager->getObject(
            ApplyCustomerIdObserver::class,
            [
                'ePersistentData' => $this->historyHelperMoc,
                'persistentSession' => $this->sessionHelperMock,
                'mPersistentData' => $this->persistentHelperMock
            ]
        );
    }

    public function testApplyPersistentCustomerIdIfPersistentDataCantProcess()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(false);
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentCustomerIdIfCannotCompareProduct()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(true);
        $this->historyHelperMoc->expects($this->once())
            ->method('isCompareProductsPersist')
            ->willReturn(false);
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentCustomerIdSuccess()
    {
        $customerId = 1;
        $observerMock = $this->createMock(Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(true);
        $this->historyHelperMoc->expects($this->once())
            ->method('isCompareProductsPersist')
            ->willReturn(true);

        $actionMock = $this->getMockBuilder(ActionStub::class)
            ->addMethods(['setCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $actionMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getControllerAction'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getControllerAction')
            ->willReturn($actionMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $sessionMock = $this->getMockBuilder(\Magento\Persistent\Model\Session::class)->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $sessionMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->willReturn($sessionMock);

        $this->subject->execute($observerMock);
    }
}
