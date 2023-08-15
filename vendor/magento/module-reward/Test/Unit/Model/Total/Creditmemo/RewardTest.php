<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Total\Creditmemo;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Model\Total\Creditmemo\Reward;
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

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(Reward::class);
    }

    /**
     * @param array $amounts
     * @dataProvider totalsNoRewardPointsLeftToRefund
     */
    public function testCollectTotalsNoRewardPointsLeftToRefund(array $amounts): void
    {
        $creditmemo = $this->getCreditmemo($amounts);

        $creditmemo->expects($this->never())->method('setRewardPointsBalance');
        $creditmemo->expects($this->never())->method('setRewardCurrencyAmount');
        $creditmemo->expects($this->never())->method('setBaseRewardCurrencyAmount');

        $this->model->collect($creditmemo);
    }

    /**
     * @return array
     */
    public function totalsNoRewardPointsLeftToRefund(): array
    {
        return [
            [
                'Amount to refund exists' => [
                    'order' => [
                        'baseRewardCurrencyAmount' => 0,
                        'baseRewardCurrencyAmountInvoiced' => 100,
                        'baseRewardCurrencyAmountRefunded' => 10,
                    ],
                ],
            ],
            [
                'Amount to refund non-zero' => [
                    'order' => [
                        'baseRewardCurrencyAmount' => 10,
                        'baseRewardCurrencyAmountInvoiced' => 100,
                        'baseRewardCurrencyAmountRefunded' => 100,
                    ],
                ]
            ],
        ];
    }

    /**
     * @param array $amounts
     * @return Creditmemo|MockObject
     */
    private function getCreditmemo(array $amounts): Creditmemo
    {
        $creditmemo = $this->getMockBuilder(Creditmemo::class)
            ->setMethods(
                [
                    'getOrder',
                    'setRewardPointsBalance',
                    'setRewardCurrencyAmount',
                    'setBaseRewardCurrencyAmount',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $creditmemo->method('getOrder')->willReturn($this->getOrder($amounts));

        return $creditmemo;
    }

    /**
     * @param array $amounts
     * @return Order|MockObject
     */
    private function getOrder(array $amounts): Order
    {
        $order = $this->getMockBuilder(Order::class)
            ->setMethods(
                [
                    'getBaseRewardCurrencyAmount',
                    'getBaseRwrdCrrncyAmtInvoiced',
                    'getBaseRwrdCrrncyAmntRefnded',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $order->method('getBaseRewardCurrencyAmount')
            ->willReturn($amounts['order']['baseRewardCurrencyAmount']);
        $order->method('getBaseRwrdCrrncyAmtInvoiced')
            ->willReturn($amounts['order']['baseRewardCurrencyAmountInvoiced']);
        $order->method('getBaseRwrdCrrncyAmntRefnded')
            ->willReturn($amounts['order']['baseRewardCurrencyAmountRefunded']);

        return $order;
    }
}
