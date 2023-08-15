<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Total\Invoice;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Model\Total\Invoice\Reward as InvoiceTotalReward;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\TestCase;

class RewardTest extends TestCase
{
    /**
     * @var \Magento\Reward\Model\Total\Creditmemo\Reward
     */
    protected $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(InvoiceTotalReward::class);
    }

    /**
     * baseRewardCurrecnyAmountLeft == 0
     */
    public function testCollectIfBaseRewardCurrencyAmountLeftIsZero()
    {
        $invoiceMock = $this->createPartialMock(
            Invoice::class,
            ['getOrder', 'getBaseGrandTotal']
        );

        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(
                [
                    'getRewardCurrencyAmount',
                    'getRwrdCurrencyAmountInvoiced',
                    'getBaseRewardCurrencyAmount',
                    'getBaseRwrdCrrncyAmtInvoiced'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $invoiceMock->expects($this->never())->method('getBaseGrandTotal');

        $orderMock->expects($this->once())->method('getRewardCurrencyAmount')->willReturn(1);
        $orderMock->expects($this->once())->method('getRwrdCurrencyAmountInvoiced')->willReturn(1);
        $orderMock->expects($this->exactly(2))->method('getBaseRewardCurrencyAmount')->willReturn(1);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(1);

        $this->assertEquals($this->model, $this->model->collect($invoiceMock));
    }

    /**
     *  baseRewardCurrencyAmount == 0
     */
    public function testCollectIfBaseRewardCurrencyAmountIsZero()
    {
        $invoiceMock = $this->createPartialMock(
            Invoice::class,
            ['getOrder', 'getBaseGrandTotal']
        );

        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(
                [
                    'getRewardCurrencyAmount',
                    'getRwrdCurrencyAmountInvoiced',
                    'getBaseRewardCurrencyAmount',
                    'getBaseRwrdCrrncyAmtInvoiced'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $invoiceMock->expects($this->never())->method('getBaseGrandTotal');

        $orderMock->expects($this->once())->method('getRewardCurrencyAmount')->willReturn(1);
        $orderMock->expects($this->once())->method('getRwrdCurrencyAmountInvoiced')->willReturn(1);
        $orderMock->expects($this->exactly(2))->method('getBaseRewardCurrencyAmount')->willReturn(0);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(1);

        $this->assertEquals($this->model, $this->model->collect($invoiceMock));
    }

    /**
     *  baseRewardCurrecnyAmountLeft > baseGrandTotal
     */
    public function testCollectIfBaseRewardCurrencyAmountLeftGreaterThanZero()
    {
        $invoiceMock = $this->getMockBuilder(Invoice::class)
            ->addMethods(['setRewardPointsBalance', 'setRewardCurrencyAmount', 'setBaseRewardCurrencyAmount'])
            ->onlyMethods(['getOrder', 'getBaseGrandTotal', 'getGrandTotal', 'setGrandTotal', 'setBaseGrandTotal'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(
                [
                    'getRewardCurrencyAmount',
                    'getRwrdCurrencyAmountInvoiced',
                    'getBaseRewardCurrencyAmount',
                    'getBaseRwrdCrrncyAmtInvoiced',
                    'getRewardPointsBalance'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $invoiceMock->expects($this->exactly(2))->method('getBaseGrandTotal')->willReturn(1);
        $invoiceMock->expects($this->once())->method('getGrandTotal')->willReturn(10);

        $invoiceMock->expects($this->once())->method('setGrandTotal')->with(0)->willReturnSelf();
        $invoiceMock->expects($this->once())->method('setBaseGrandTotal')->with(0)->willReturnSelf();

        $invoiceMock->expects($this->once())->method('setRewardPointsBalance')->with(3)->willReturnSelf();
        $invoiceMock->expects($this->once())->method('setRewardCurrencyAmount')->with(10)->willReturnSelf();
        $invoiceMock->expects($this->once())->method('setBaseRewardCurrencyAmount')->with(1)->willReturnSelf();

        $orderMock->expects($this->once())->method('getRewardCurrencyAmount')->willReturn(1);
        $orderMock->expects($this->once())->method('getRwrdCurrencyAmountInvoiced')->willReturn(1);
        $orderMock->expects($this->exactly(3))->method('getBaseRewardCurrencyAmount')->willReturn(20);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(1);
        $orderMock->expects($this->exactly(2))->method('getRewardPointsBalance')->willReturn(50);

        $this->assertEquals($this->model, $this->model->collect($invoiceMock));
    }

    /**
     *  baseRewardCurrecnyAmountLeft < baseGrandTotal
     */
    public function testCollectIfBaseRewardCurrencyAmountLeftLessThanZero()
    {
        $invoiceMock = $this->getMockBuilder(Invoice::class)
            ->addMethods(['setRewardPointsBalance', 'setRewardCurrencyAmount', 'setBaseRewardCurrencyAmount'])
            ->onlyMethods(['getOrder', 'getBaseGrandTotal', 'getGrandTotal', 'setGrandTotal', 'setBaseGrandTotal'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(
                [
                    'getRewardCurrencyAmount',
                    'getRwrdCurrencyAmountInvoiced',
                    'getBaseRewardCurrencyAmount',
                    'getBaseRwrdCrrncyAmtInvoiced',
                    'getRewardPointsBalance'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $invoiceMock->expects($this->exactly(2))->method('getBaseGrandTotal')->willReturn(3);
        $invoiceMock->expects($this->once())->method('getGrandTotal')->willReturn(10);

        $invoiceMock->expects($this->once())->method('setGrandTotal')->with(10)->willReturnSelf();
        $invoiceMock->expects($this->once())->method('setBaseGrandTotal')->with(1)->willReturnSelf();

        $invoiceMock->expects($this->once())->method('setRewardPointsBalance')->with(6)->willReturnSelf();
        $invoiceMock->expects($this->once())->method('setRewardCurrencyAmount')->with(0)->willReturnSelf();
        $invoiceMock->expects($this->once())->method('setBaseRewardCurrencyAmount')->with(2)->willReturnSelf();

        $orderMock->expects($this->any())->method('getRewardCurrencyAmount')->willReturn(1);
        $orderMock->expects($this->any())->method('getRwrdCurrencyAmountInvoiced')->willReturn(1);
        $orderMock->expects($this->exactly(3))->method('getBaseRewardCurrencyAmount')->willReturn(20);
        $orderMock->expects($this->any())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(18);
        $orderMock->expects($this->exactly(2))->method('getRewardPointsBalance')->willReturn(50);

        $this->assertEquals($this->model, $this->model->collect($invoiceMock));
    }
}
