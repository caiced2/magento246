<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Observer\CreditmemoDataImportObserver;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Math\FloatComparator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[\AllowDynamicProperties] //@phpstan-ignore-line
class CreditmemoDataImportObserverTest extends TestCase
{
    /**
     * @var $refundAmount
     */
    private static $refundAmount = 10;

    /**
     * @var CreditmemoDataImportObserver
     */
    private $model;

    /**
     * @var PriceCurrency|MockObject
     */
    private $priceCurrency;

    /**
     * @var DataObject|MockObject
     */
    private $event;

    /**
     * @var Creditmemo|MockObject
     */
    private $creditmemo;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var OrderMock|MockObject
     */
    private $orderMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->priceCurrency = $this->getMockBuilder(PriceCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->observer = $objectManagerHelper->getObject(
            Observer::class,
            ['event' => $this->event]
        );

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrder'])
            ->getMock();

        $this->model = $objectManagerHelper->getObject(
            CreditmemoDataImportObserver::class,
            [
                'priceCurrency' => $this->priceCurrency,
                'floatComparator' => new FloatComparator()
            ]
        );
    }

    /**
     * @return void
     */
    public function testCreditmemoDataImport(): void
    {
        $rate = 2;
        $dataInput = [
            'refund_customerbalance_return' => self::$refundAmount,
            'refund_customerbalance_return_enable' => true,
            'refund_customerbalance' => true,
            'refund_real_customerbalance' => true
        ];

        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemo->setBaseCustomerBalanceReturnMax(self::$refundAmount);
        $this->creditmemo->setBaseGrandTotal(self::$refundAmount);

        $this->priceCurrency
            ->method('round')
            ->withConsecutive(
                [self::$refundAmount],
                [self::$refundAmount * $rate]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnArgument(0),
                $this->returnArgument(0)
            );

        $orderMock = $this->getMockBuilder(Order::class)->disableOriginalConstructor()
            ->onlyMethods(['getBaseToOrderRate'])
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getBaseToOrderRate')
            ->willReturn($rate);

        $this->creditmemo->method('getOrder')
            ->willReturn($orderMock);

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCreditmemo', 'getInput'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($this->creditmemo);
        $eventMock->expects($this->once())
            ->method('getInput')
            ->willReturn($dataInput);
        $observer->method('getEvent')
            ->willReturn($eventMock);

        $this->model->execute($observer);
        $this->assertEquals($this->creditmemo->getCustomerBalanceRefundFlag(), true);
        $this->assertEquals($this->creditmemo->getPaymentRefundDisallowed(), true);
        $this->assertEquals($this->creditmemo->getCustomerBalTotalRefunded(), self::$refundAmount * $rate);
        $this->assertEquals(self::$refundAmount, $this->creditmemo->getBaseCustomerBalanceReturnMax());
    }
}
