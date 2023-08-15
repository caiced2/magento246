<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Observer\ModifyRewardedAmountOnRefundObserver;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\TestCase;

class ModifyRewardedAmountOnRefundObserverTest extends TestCase
{
    /** @var ModifyRewardedAmountOnRefundObserver */
    protected $model;

    /**
     * @var Observer
     */
    protected $observer;

    /**
     * @var DataObject
     */
    protected $event;

    protected function setUp(): void
    {
        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            ModifyRewardedAmountOnRefundObserver::class
        );
    }

    /**
     * @param array $orderData
     * @param integer $baseRewardAmount
     * @param integer $expectedRewardAmount
     *
     * @dataProvider modifyRewardedAmountOnRefundDataProvider
     */
    public function testModifyRewardedAmountOnRefund($orderData, $baseRewardAmount, $expectedRewardAmount)
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getBsCustomerBalTotalRefunded'])
            ->onlyMethods(['getBaseTotalRefunded', 'getBaseTaxRefunded', 'getBaseShippingRefunded', '__sleep'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())->method('getBsCustomerBalTotalRefunded')
            ->willReturn($orderData['bs_customer_bal_total_refunded']);
        $orderMock->expects($this->any())->method('getBaseTotalRefunded')
            ->willReturn($orderData['base_total_refunded']);
        $orderMock->expects($this->any())->method('getBaseTaxRefunded')
            ->willReturn($orderData['base_tax_refunded']);
        $orderMock->expects($this->any())->method('getBaseShippingRefunded')
            ->willReturn($orderData['base_shipping_refunded']);

        $creditMemoMock = $this->getMockBuilder(Creditmemo::class)
            ->addMethods(['getRewardedAmountAfterRefund', 'setRewardedAmountAfterRefund'])
            ->onlyMethods(['getOrder', '__sleep'])
            ->disableOriginalConstructor()
            ->getMock();
        $creditMemoMock->expects($this->any())->method('getOrder')
            ->willReturn($orderMock);
        $creditMemoMock->expects($this->any())->method('getRewardedAmountAfterRefund')
            ->willReturn($baseRewardAmount);

        $creditMemoMock->expects($this->once())->method('setRewardedAmountAfterRefund')->with($expectedRewardAmount);
        $this->event->setCreditmemo($creditMemoMock);

        $this->model->execute($this->observer);
    }

    /**
     * @return array
     */
    public function modifyRewardedAmountOnRefundDataProvider()
    {
        return [
            [
                'orderData' => [
                    'bs_customer_bal_total_refunded' => 100,
                    'base_total_refunded' => 40,
                    'base_tax_refunded' => 10,
                    'base_shipping_refunded' => 10,
                ],
                'baseRewardAmount' => 5,
                'expectedRewardAmount' => 25,
            ],
            [
                'orderData' => [
                    'bs_customer_bal_total_refunded' => 10,
                    'base_total_refunded' => 40,
                    'base_tax_refunded' => 10,
                    'base_shipping_refunded' => 10,
                ],
                'baseRewardAmount' => 10,
                'expectedRewardAmount' => 20
            ]
        ];
    }
}
