<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerBalance\Helper\Data;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Observer\CustomerSaveAfterObserver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerSaveAfterObserverTest extends TestCase
{
    /** @var CustomerSaveAfterObserver */
    protected $observer;

    /** @var BalanceFactory|MockObject */
    protected $balanceFactory;

    /** @var StoreManagerInterface|MockObject */
    protected $storeManager;

    /** @var Data|MockObject */
    protected $customerBalanceData;

    /** @var Observer|MockObject */
    protected $eventObserver;

    /** @var RequestInterface|MockObject */
    protected $request;

    /** @var CustomerInterface|MockObject */
    protected $customer;

    /** @var Balance|MockObject */
    protected $balance;

    /** @var StoreInterface|MockObject */
    protected $store;

    protected function setUp(): void
    {
        $this->balanceFactory = $this->createPartialMock(
            BalanceFactory::class,
            ['create']
        );
        $this->storeManager = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->customerBalanceData = $this->createMock(Data::class);

        $this->observer = new CustomerSaveAfterObserver(
            $this->balanceFactory,
            $this->storeManager,
            $this->customerBalanceData
        );

        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->addMethods(['getRequest', 'getCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getPost']
        );
        $this->customer = $this->getMockForAbstractClass(
            CustomerInterface::class,
            [],
            '',
            false
        );
        $this->balance = $this->getMockBuilder(Balance::class)
            ->addMethods(['setCustomer', 'setWebsiteId', 'setAmountDelta', 'setComment'])
            ->onlyMethods(['setNotifyByEmail', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->store = $this->getMockForAbstractClass(StoreInterface::class, [], '', false);
    }

    public function testExecuteWithDisabledCustomerBalance()
    {
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->assertNull($this->observer->execute($this->eventObserver));
    }

    public function testExecuteWithEmailNotification()
    {
        $post = [
            'amount_delta' => 1000,
            'website_id' => 1,
            'store_id' => 1,
            'comment' => 'comment',
            'notify_by_email' => 1,
        ];
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->eventObserver->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->request->expects($this->once())
            ->method('getPost')
            ->with('customerbalance')
            ->willReturn($post);
        $this->eventObserver->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customer);
        $this->balanceFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->balance);
        $this->balance->expects($this->once())
            ->method('setCustomer')
            ->with($this->customer)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setWebsiteId')
            ->with(1)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setAmountDelta')
            ->with(1000)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setComment')
            ->with('comment')
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setNotifyByEmail')
            ->with(true, 1);
        $this->balance->expects($this->once())
            ->method('save');

        $this->observer->execute($this->eventObserver);
    }

    public function testExecuteWithEmailNotificationAndSingleStoreMode()
    {
        $storeId = 1;
        $post = [
            'amount_delta' => 1000,
            'website_id' => 1,
            'comment' => 'comment',
            'notify_by_email' => 1,
        ];
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->eventObserver->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->request->expects($this->once())
            ->method('getPost')
            ->with('customerbalance')
            ->willReturn($post);
        $this->eventObserver->expects($this->once())
            ->method('getCustomer')
            ->willReturn($this->customer);
        $this->balanceFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->balance);
        $this->balance->expects($this->once())
            ->method('setCustomer')
            ->with($this->customer)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setWebsiteId')
            ->with(1)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setAmountDelta')
            ->with(1000)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setComment')
            ->with('comment')
            ->willReturnSelf();
        $this->storeManager->expects($this->once())
            ->method('isSingleStoreMode')
            ->willReturn(true);
        $this->storeManager->expects($this->once())
            ->method('getStores')
            ->willReturn([$this->store]);
        $this->store->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $this->balance->expects($this->once())
            ->method('setNotifyByEmail')
            ->with(true, $storeId);
        $this->balance->expects($this->once())
            ->method('save');

        $this->observer->execute($this->eventObserver);
    }
}
