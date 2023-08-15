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
use Magento\Reward\Observer\SetRewardPointsBalanceToRefund;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\TestCase;

class SetRewardPointsBalanceToRefundTest extends TestCase
{
    /**
     * @var SetRewardPointsBalanceToRefund
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(SetRewardPointsBalanceToRefund::class);
    }

    public function testSetRewardPointsBalanceIfPointsBalanceInNull()
    {
        $observerMock = $this->createMock(Observer::class);
        $creditmemoMock = $this->createMock(Creditmemo::class);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInput', 'getCreditmemo'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInput')->willReturn([]);
        $eventMock->expects($this->once())->method('getCreditmemo')->willReturn($creditmemoMock);

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetRewardPointsBalanceIfRewardsRefundNotSet()
    {
        $observerMock = $this->createMock(Observer::class);
        $creditmemoMock = $this->createMock(Creditmemo::class);

        $inputData = ['refund_reward_points' => 100];

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInput', 'getCreditmemo'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInput')->willReturn($inputData);
        $eventMock->expects($this->once())->method('getCreditmemo')->willReturn($creditmemoMock);

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetRewardPointsBalanceIfRewardsDisabled()
    {
        $observerMock = $this->createMock(Observer::class);
        $creditmemoMock = $this->createMock(Creditmemo::class);

        $inputData = [
            'refund_reward_points' => 100,
            'refund_reward_points_enable' => false,
        ];

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInput', 'getCreditmemo'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInput')->willReturn($inputData);
        $eventMock->expects($this->once())->method('getCreditmemo')->willReturn($creditmemoMock);

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetRewardPointsBalanceIfCreditMemoRewardsBalanceIsZero()
    {
        $observerMock = $this->createMock(Observer::class);
        $creditmemoMock = $this->getMockBuilder(Creditmemo::class)
            ->addMethods(['getRewardPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();

        $inputData = [
            'refund_reward_points' => 100,
            'refund_reward_points_enable' => true,
        ];

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInput', 'getCreditmemo'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInput')->willReturn($inputData);
        $eventMock->expects($this->once())->method('getCreditmemo')->willReturn($creditmemoMock);

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);
        $creditmemoMock->expects($this->once())->method('getRewardPointsBalance')->willReturn(0);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetRewardPointsBalanceSuccess()
    {
        $observerMock = $this->createMock(Observer::class);
        $creditmemoMock = $this->getMockBuilder(Creditmemo::class)
            ->addMethods(['getRewardPointsBalance', 'setRewardPointsBalanceRefund'])
            ->disableOriginalConstructor()
            ->getMock();

        $inputData = [
            'refund_reward_points' => 100,
            'refund_reward_points_enable' => true,
        ];

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInput', 'getCreditmemo'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInput')->willReturn($inputData);
        $eventMock->expects($this->once())->method('getCreditmemo')->willReturn($creditmemoMock);
        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $creditmemoMock->expects($this->once())->method('getRewardPointsBalance')->willReturn(50);
        $creditmemoMock->expects($this->once())
            ->method('setRewardPointsBalanceRefund')
            ->with(50)->willReturnSelf();

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
