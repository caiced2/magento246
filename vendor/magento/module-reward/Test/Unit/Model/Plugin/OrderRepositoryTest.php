<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Reward\Model\Plugin\OrderRepository;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for testing plugin to order repository.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class OrderRepositoryTest extends TestCase
{
    /**
     * @var OrderRepository
     */
    private $model;

    /**
     * @var MockObject
     */
    private $orderMock;

    /**
     * @var MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var OrderExtensionInterface
     */
    private $orderExtensionMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(
                [
                    'setForcedCanCreditmemo',
                    'getBaseRwrdCrrncyAmntRefnded',
                    'getBaseRwrdCrrncyAmtInvoiced',
                    'getRewardPointsBalance',
                    'getRewardCurrencyAmount',
                    'getBaseRewardCurrencyAmount'
                ]
            )
            ->onlyMethods(
                ['canUnhold', 'isCanceled', 'getState', 'getExtensionAttributes', 'getData', 'setExtensionAttributes']
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepositoryMock = $this->getMockForAbstractClass(OrderRepositoryInterface::class);
        $this->orderExtensionMock = $this->getMockBuilder(OrderExtensionInterface::class)
            ->setMethods(
                [
                    'setRewardPointsBalance',
                    'setRewardCurrencyAmount',
                    'setBaseRewardCurrencyAmount'
                ]
            )
            ->getMockForAbstractClass();
        $this->model = new OrderRepository();
    }

    /**
     * Method for testing after get does not force.
     *
     * @param bool $canUnhold
     * @param bool $isCanceled
     * @param string $state
     * @dataProvider nonrefundableOrderStateDataProvider
     * @return void
     */
    public function testAfterGetDoesNotForceCreditmemoIfOrderStateDoesNotAllowIt(
        $canUnhold,
        $isCanceled,
        $state,
        $rewardAmountInvoiced,
        $rewardAmountRefunded
    ) {
        $orderMock = $this->orderMock;
        $orderMock->expects($this->any())->method('canUnhold')->willReturn($canUnhold);
        $orderMock->expects($this->any())->method('isCanceled')->willReturn($isCanceled);
        $orderMock->expects($this->any())->method('getState')->willReturn($state);
        $orderMock->expects($this->any())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn($rewardAmountInvoiced);
        $orderMock->expects($this->any())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn($rewardAmountRefunded);
        $orderMock->expects($this->never())->method('setForcedCanCreditmemo')->with(true);
        $orderMock->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturn($this->orderExtensionMock);
        $orderMock->expects($this->atLeastOnce())->method('getData')->willReturn(10);
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setRewardPointsBalance')->willReturnSelf();
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setRewardCurrencyAmount')->willReturnSelf();
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setBaseRewardCurrencyAmount')
            ->willReturnSelf();
        $orderMock->expects($this->atLeastOnce())->method('setExtensionAttributes')->with($this->orderExtensionMock);

        $this->assertEquals($orderMock, $this->model->afterGet($this->orderRepositoryMock, $orderMock, 1));
    }

    /**
     * Data provider for method GetDoesNotForce.
     *
     * @return array
     */
    public function nonrefundableOrderStateDataProvider()
    {
        return [
            [false, false, Order::STATE_NEW, 10, 10],
            [false, false, Order::STATE_CLOSED, 20, 10],
            [false, true, Order::STATE_CLOSED, 10, 10],
            [true, false, Order::STATE_CLOSED, 20, 10],
            [true, true, Order::STATE_CLOSED, 10, 10],
        ];
    }

    /**
     * Method for testing after get does force.
     *
     * @return void
     */
    public function testAfterGetForcesCreditmemoIfOrderStateAllowsIt()
    {
        $orderMock = $this->orderMock;
        $orderMock->expects($this->any())->method('canUnhold')->willReturn(false);
        $orderMock->expects($this->any())->method('isCanceled')->willReturn(false);
        $orderMock->expects($this->any())->method('getState')->willReturn(Order::STATE_NEW);
        $orderMock->expects($this->any())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(100);
        $orderMock->expects($this->any())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn(50);

        $orderMock->expects($this->once())->method('setForcedCanCreditmemo')->with(true);

        $orderMock->expects($this->once())->method('getExtensionAttributes')->willReturn($this->orderExtensionMock);
        $orderMock->expects($this->atLeastOnce())->method('getData')->willReturn(10);
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setRewardPointsBalance')->willReturnSelf();
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setRewardCurrencyAmount')->willReturnSelf();
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setBaseRewardCurrencyAmount')
            ->willReturnSelf();
        $orderMock->expects($this->atLeastOnce())->method('setExtensionAttributes')->with($this->orderExtensionMock);

        $this->assertEquals($orderMock, $this->model->afterGet($this->orderRepositoryMock, $orderMock, 1));
    }
}
