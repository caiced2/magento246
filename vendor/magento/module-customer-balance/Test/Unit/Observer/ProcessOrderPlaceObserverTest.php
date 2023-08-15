<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Helper\Data;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\Balance\History;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Observer\CheckStoreCreditBalance;
use Magento\CustomerBalance\Observer\ProcessOrderPlaceObserver;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessOrderPlaceObserverTest extends TestCase
{
    /** @var ProcessOrderPlaceObserver */
    protected $model;

    /**
     * @var Observer
     */
    protected $observer;

    /**
     * @var DataObject
     */
    protected $event;

    /**
     * @var Data|MockObject
     */
    protected $customerBalanceData;

    /**
     * @var Store|MockObject
     */
    protected $store;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var Balance|MockObject
     */
    protected $balance;

    /**
     * @var BalanceFactory|MockObject
     */
    protected $balanceFactory;

    /**
     * @var CheckStoreCreditBalance|MockObject
     */
    protected $checkStoreCreditBalance;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->customerBalanceData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->checkStoreCreditBalance = $this
            ->getMockBuilder(CheckStoreCreditBalance::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->balance = $this->getMockBuilder(Balance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCustomerId',
                'setWebsiteId',
                'setAmountDelta',
                'setHistoryAction',
                'setOrder',
                'save',
                'loadByCustomer',
                'getAmount',
            ])
            ->getMock();

        $this->balanceFactory = $this->getMockBuilder(BalanceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->balanceFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->balance);

        $this->model = $objectManagerHelper->getObject(
            ProcessOrderPlaceObserver::class,
            [
                'balanceFactory' => $this->balanceFactory,
                'customerBalanceData' => $this->customerBalanceData,
                'storeManager' => $this->storeManager,
                'checkStoreCreditBalance' => $this->checkStoreCreditBalance,
            ]
        );

        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);
    }

    public function testProcessOrderPlaceCustomerBalanceDisabled()
    {
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->assertEquals($this->model, $this->model->execute($this->observer));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessOrderPlaceCustomerBalanceEnabled()
    {
        $baseCustomerBalAmountUsed = 1.;
        $customerBalanceAmountUsed = 1.;
        $storeId = 1;
        $websiteId = 1;
        $customerId = 1;

        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        /** @var Order|MockObject $orderMock */
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setBaseCustomerBalanceAmount',
                'setCustomerBalanceAmount',
                'getBaseCustomerBalanceAmount',
                'getStoreId',
                'getCustomerId',
            ])
            ->getMock();
        $orderMock->expects($this->once())
            ->method('setBaseCustomerBalanceAmount')
            ->with($baseCustomerBalAmountUsed)
            ->willReturnSelf();
        $orderMock->expects($this->once())
            ->method('setCustomerBalanceAmount')
            ->with($customerBalanceAmountUsed)
            ->willReturnSelf();
        $orderMock->expects($this->exactly(2))
            ->method('getBaseCustomerBalanceAmount')
            ->willReturn($baseCustomerBalAmountUsed);
        $orderMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $orderMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        /** @var Quote|MockObject $quoteMock */
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUseCustomerBalance',
                'getBaseCustomerBalAmountUsed',
                'getCustomerBalanceAmountUsed',
            ])
            ->getMock();
        $quoteMock->expects($this->once())
            ->method('getUseCustomerBalance')
            ->willReturn(true);
        $quoteMock->expects($this->once())
            ->method('getBaseCustomerBalAmountUsed')
            ->willReturn($baseCustomerBalAmountUsed);
        $quoteMock->expects($this->once())
            ->method('getCustomerBalanceAmountUsed')
            ->willReturn($customerBalanceAmountUsed);

        $this->event->setOrder($orderMock);
        $this->event->setQuote($quoteMock);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($this->store);
        $this->store->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->balance->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setWebsiteId')
            ->with($websiteId)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setAmountDelta')
            ->with(-$baseCustomerBalAmountUsed)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setHistoryAction')
            ->with(History::ACTION_USED)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setOrder')
            ->with($orderMock)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->checkStoreCreditBalance
            ->expects($this->once())
            ->method('execute')
            ->with($orderMock);

        $this->assertEquals($this->model, $this->model->execute($this->observer));
    }
}
