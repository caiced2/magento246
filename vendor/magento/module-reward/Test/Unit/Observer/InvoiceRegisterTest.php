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
use Magento\Reward\Observer\InvitationToCustomer;
use Magento\Reward\Observer\InvoiceRegister;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use PHPUnit\Framework\TestCase;

class InvoiceRegisterTest extends TestCase
{
    /**
     * @var InvitationToCustomer
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);
        $this->subject = $objectManager->getObject(InvoiceRegister::class);
    }

    public function testAddRewardsIfRewardCurrencyAmountIsNull()
    {
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $invoiceMock = $this->getMockBuilder(Invoice::class)
            ->addMethods(['getBaseRewardCurrencyAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects($this->once())->method('getBaseRewardCurrencyAmount')->willReturn(null);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInvoice'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInvoice')->willReturn($invoiceMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testAddRewardsSuccess()
    {
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $invoiceMock = $this->getMockBuilder(Invoice::class)
            ->addMethods(['getBaseRewardCurrencyAmount', 'getRewardCurrencyAmount'])
            ->onlyMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects($this->exactly(2))->method('getBaseRewardCurrencyAmount')->willReturn(100);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getInvoice'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInvoice')->willReturn($invoiceMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(
                [
                    'getRwrdCurrencyAmountInvoiced',
                    'getBaseRwrdCrrncyAmtInvoiced',
                    'setRwrdCurrencyAmountInvoiced',
                    'setBaseRwrdCrrncyAmtInvoiced'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getRwrdCurrencyAmountInvoiced')->willReturn(50);
        $orderMock->expects($this->once())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(50);
        $orderMock->expects($this->once())
            ->method('setRwrdCurrencyAmountInvoiced')
            ->with(100)->willReturnSelf();
        $orderMock->expects($this->once())
            ->method('setBaseRwrdCrrncyAmtInvoiced')
            ->with(150)->willReturnSelf();

        $invoiceMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $invoiceMock->expects($this->once())->method('getRewardCurrencyAmount')->willReturn(50);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
