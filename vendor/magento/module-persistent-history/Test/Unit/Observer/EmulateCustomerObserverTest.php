<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Persistent\Helper\Session;
use Magento\PersistentHistory\Helper\Data;
use Magento\PersistentHistory\Model\CustomerEmulator;
use Magento\PersistentHistory\Observer\EmulateCustomerObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmulateCustomerObserverTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $ePersistentDataMock;

    /**
     * @var MockObject
     */
    protected $mPersistentDataMock;

    /**
     * @var MockObject
     */
    protected $customerSessionMock;

    /**
     * @var MockObject
     */
    protected $persistentSessionMock;

    /**
     * @var MockObject
     */
    protected $emulatorMock;

    /**
     * @var EmulateCustomerObserver
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->ePersistentDataMock = $this->createPartialMock(
            Data::class,
            ['isCustomerAndSegmentsPersist']
        );
        $this->persistentSessionMock = $this->createMock(Session::class);

        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->emulatorMock = $this->createMock(CustomerEmulator::class);
        $this->mPersistentDataMock = $this->createPartialMock(
            \Magento\Persistent\Helper\Data::class,
            ['canProcess']
        );

        $this->subject = $objectManager->getObject(
            EmulateCustomerObserver::class,
            [
                'ePersistentData' => $this->ePersistentDataMock,
                'persistentSession' => $this->persistentSessionMock,
                'mPersistentData' => $this->mPersistentDataMock,
                'customerSession' => $this->customerSessionMock,
                'customerEmulator' => $this->emulatorMock
            ]
        );
    }

    public function testSetPersistentDataIfDataCannotBeProcessed()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(false);
        $this->subject->execute($observerMock);
    }

    public function testSetPersistentDataIfCustomerIsNotPersistent()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(true);
        $this->ePersistentDataMock->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->willReturn(false);
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataIfSessionNotPersistent()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(true);
        $this->ePersistentDataMock->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->willReturn(true);
        $this->persistentSessionMock->expects($this->once())->method('isPersistent')->willReturn(false);
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataIfUserLoggedIn()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(true);
        $this->ePersistentDataMock->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->willReturn(true);
        $this->persistentSessionMock->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataSuccess()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->willReturn(true);
        $this->ePersistentDataMock->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->willReturn(true);
        $this->persistentSessionMock->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->emulatorMock->expects($this->once())->method('emulate');
        $this->subject->execute($observerMock);
    }
}
