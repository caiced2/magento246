<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Observer\PaymentDataImportObserver;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentDataImportObserverTest extends TestCase
{
    /**
     * @var PaymentDataImportObserver
     */
    protected $observer;

    /**
     * @var MockObject
     */
    protected $customerBalanceMock;

    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    /**
     * @var MockObject
     */
    protected $quoteMock;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var MockObject
     */
    protected $eventMock;

    /**
     * @var MockObject
     */
    protected $paymentMock;

    protected function setUp(): void
    {
        $this->customerBalanceMock = $this->getMockBuilder(Balance::class)
            ->addMethods(['setCustomerId', 'setWebsiteId'])
            ->onlyMethods(['loadByCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId', 'getIsMultiShipping', 'setUseCustomerBalance', 'setCustomerBalanceInstance'])
            ->onlyMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock = $this->createMock(Observer::class);
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getPayment', 'getInput'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this->createMock(Payment::class);
        $this->observer = new PaymentDataImportObserver(
            $this->customerBalanceMock,
            $this->storeManagerMock
        );
    }

    public function testExecuteForNotMultishippingQuote()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(false);
        $this->eventMock->expects($this->never())->method('getInput');
        $this->observer->execute($this->observerMock);
    }

    public function testExecuteForMultishippingQuote()
    {
        $storeId = 1;
        $customerId = 2;
        $storeMock = $this->getMockForAbstractClass(StoreInterface::class);
        $inputMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getAdditionalData', 'getMethod', 'setMethod'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->once())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(true);
        $this->eventMock->expects($this->once())->method('getInput')->willReturn($inputMock);
        $inputMock->expects($this->once())->method('getAdditionalData')->willReturn(['use_customer_balance' => true]);
        $this->quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->once())->method('getStore')->with($storeId)->willReturn($storeMock);
        $this->quoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->quoteMock->expects($this->once())->method('setUseCustomerBalance')->willReturn(true);
        $this->customerBalanceMock
            ->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn(2);
        $this->customerBalanceMock->expects($this->once())->method('setWebsiteId')->with(2)->willReturnSelf();
        $this->customerBalanceMock->expects($this->once())->method('loadByCustomer')->willReturnSelf();
        $this->quoteMock
            ->expects($this->once())
            ->method('setCustomerBalanceInstance')
            ->with($this->customerBalanceMock);
        $inputMock->expects($this->once())->method('getMethod')->willReturn(null);
        $inputMock->expects($this->once())->method('setMethod')->with('free');
        $this->observer->execute($this->observerMock);
    }
}
