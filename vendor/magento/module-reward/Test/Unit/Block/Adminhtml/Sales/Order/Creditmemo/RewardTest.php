<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Reward\Block\Adminhtml\Sales\Order\Creditmemo\Reward;
use Magento\Reward\Helper\Data;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RewardTest extends TestCase
{
    /**
     * @var Reward
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $registryMock;

    /**
     * @var MockObject
     */
    protected $rewardHelperMock;

    protected function setUp(): void
    {
        $this->registryMock = $this->createMock(Registry::class);
        $this->rewardHelperMock = $this->createMock(Data::class);
        $contextMock = $this->createMock(Context::class);

        $this->model = new Reward(
            $contextMock,
            $this->registryMock,
            $this->rewardHelperMock
        );
    }

    public function testGetCreditmemo()
    {
        $creditmemoMock = $this->createMock(Creditmemo::class);
        $this->registryMock->expects($this->once())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $this->assertEquals($creditmemoMock, $this->model->getCreditmemo());
    }

    /**
     * Check that refund is not possible for guest.
     */
    public function testCanRefundRewardPointsWithGuest()
    {
        $creditmemoMock = $this->createMock(Creditmemo::class);
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getRewardCurrencyAmount'])
            ->onlyMethods(['getCustomerIsGuest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock->expects($this->atLeastOnce())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $creditmemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomerIsGuest')->willReturn(true);

        $orderMock->expects($this->never())->method('getRewardCurrencyAmount');
        $this->assertFalse($this->model->canRefundRewardPoints());
    }

    /**
     * Check that refund is not possible when order has no used reward points.
     */
    public function testCanRefundRewardPointsWithNoReward()
    {
        $creditmemoMock = $this->createMock(Creditmemo::class);
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getRewardCurrencyAmount'])
            ->onlyMethods(['getCustomerIsGuest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock->expects($this->atLeastOnce())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $creditmemoMock->expects($this->atLeastOnce())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomerIsGuest')->willReturn(false);
        $orderMock->expects($this->once())->method('getRewardCurrencyAmount')->willReturn(0);

        $this->assertFalse($this->model->canRefundRewardPoints());
    }

    /**
     * Check that it is possible to refund reward points.
     */
    public function testCanRefundRewardPoints()
    {
        $creditmemoMock = $this->createMock(Creditmemo::class);
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getRewardCurrencyAmount'])
            ->onlyMethods(['getCustomerIsGuest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock->expects($this->atLeastOnce())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $creditmemoMock->expects($this->atLeastOnce())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomerIsGuest')->willReturn(false);
        $orderMock->expects($this->once())->method('getRewardCurrencyAmount')->willReturn(75);

        $this->assertTrue($this->model->canRefundRewardPoints());
    }

    public function testGetRefundRewardPointsBalance()
    {
        $refundPointsBalance = "75";
        $creditmemoMock = $this->createMock(Creditmemo::class);
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getRewardPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock->expects($this->once())->method('registry')->with('current_creditmemo')
            ->willReturn($creditmemoMock);

        $creditmemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getRewardPointsBalance')->willReturn($refundPointsBalance);

        $this->assertEquals((int)$refundPointsBalance, $this->model->getRefundRewardPointsBalance());
    }

    public function testIsAutoRefundEnabled()
    {
        $this->rewardHelperMock->expects($this->once())->method('isAutoRefundEnabled')->willReturn(true);
        $this->assertTrue($this->model->isAutoRefundEnabled());
    }
}
