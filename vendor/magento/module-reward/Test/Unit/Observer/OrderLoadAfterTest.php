<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Observer\OrderLoadAfter;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;

class OrderLoadAfterTest extends TestCase
{
    /**
     * @var OrderLoadAfter
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(OrderLoadAfter::class);
    }

    public function testSetForcedCreditmemoFlagIfOrderCanUnhold()
    {
        $observerMock = $this->createMock(Observer::class);

        $orderMock = $this->createPartialMock(Order::class, ['canUnhold']);
        $orderMock->expects($this->once())->method('canUnhold')->willReturn(true);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetForcedCreditmemoFlagIfOrderIsCanceled()
    {
        $observerMock = $this->createMock(Observer::class);

        $orderMock = $this->createPartialMock(
            Order::class,
            ['canUnhold', 'isCanceled']
        );
        $orderMock->expects($this->once())->method('canUnhold')->willReturn(false);
        $orderMock->expects($this->once())->method('isCanceled')->willReturn(true);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetForcedCreditmemoFlagIfOrderStateIsClosed()
    {
        $observerMock = $this->createMock(Observer::class);

        $orderMock = $this->createPartialMock(
            Order::class,
            ['canUnhold', 'isCanceled', 'getState']
        );
        $orderMock->expects($this->once())->method('canUnhold')->willReturn(false);
        $orderMock->expects($this->once())->method('isCanceled')->willReturn(false);
        $orderMock->expects($this->once())
            ->method('getState')
            ->willReturn(Order::STATE_CLOSED);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetForcedCreditmemoFlagIfRewardAmountIsZero()
    {
        $observerMock = $this->createMock(Observer::class);

        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getBaseRwrdCrrncyAmntRefnded', 'getBaseRwrdCrrncyAmtInvoiced'])
            ->onlyMethods(['canUnhold', 'isCanceled', 'getState'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('canUnhold')->willReturn(false);
        $orderMock->expects($this->once())->method('isCanceled')->willReturn(false);
        $orderMock->expects($this->once())
            ->method('getState')
            ->willReturn(Order::STATE_PROCESSING);

        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(100);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn(100);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetForcedCreditmemoFlagSuccess()
    {
        $observerMock = $this->createMock(Observer::class);

        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getBaseRwrdCrrncyAmntRefnded', 'getBaseRwrdCrrncyAmtInvoiced', 'setForcedCanCreditmemo'])
            ->onlyMethods(['canUnhold', 'isCanceled', 'getState'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('canUnhold')->willReturn(false);
        $orderMock->expects($this->once())->method('isCanceled')->willReturn(false);
        $orderMock->expects($this->once())
            ->method('getState')
            ->willReturn(Order::STATE_PROCESSING);

        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(150);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn(100);
        $orderMock->expects($this->once())->method('setForcedCanCreditmemo')->with(true)->willReturnSelf();

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
