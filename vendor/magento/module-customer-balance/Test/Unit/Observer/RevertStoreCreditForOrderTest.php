<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\Balance\History;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Observer\RevertStoreCreditForOrder;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for reverting store credit to customer account
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RevertStoreCreditForOrderTest extends TestCase
{
    /** @var RevertStoreCreditForOrder */
    private $model;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var DataObject
     */
    private $event;

    /**
     * @var Store|MockObject
     */
    private $store;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Balance|MockObject
     */
    private $balance;

    /**
     * @var BalanceFactory|MockObject|null
     */
    private $balanceFactory;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

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

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCustomerId',
                'setBaseCustomerBalanceAmount',
                'setCustomerBalanceAmount',
                'setBaseCustomerBalanceInvoiced',
                'getStoreId',
                'getCustomerId',
                'getBaseCustomerBalanceAmount',
                'getBaseCustomerBalanceInvoiced'
            ])
            ->getMock();
        $this->model = $objectManagerHelper->getObject(
            RevertStoreCreditForOrder::class,
            [
                'balanceFactory' => $this->balanceFactory,
                'storeManager' => $this->storeManager
            ]
        );

        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);
    }

    /**
     * Test revert store credit for order execute successfully
     *
     * @param float|null $baseCustomerBalAmountUsed
     * @param float $baseCustomerBalAmountInvoiced
     * @param int $storeId
     * @param int $websiteId
     * @param int|null $customerId
     * @dataProvider revertStoreCreditForOrderDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRevertStoreCreditForOrderExecuteSuccessfully(
        ?float $baseCustomerBalAmountUsed,
        float $baseCustomerBalAmountInvoiced,
        int $storeId,
        int $websiteId,
        ?int $customerId
    ): void {
        $this->orderMock->expects($this->any())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('setBaseCustomerBalanceAmount')
            ->with($customerId)
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('setBaseCustomerBalanceInvoiced')
            ->with($baseCustomerBalAmountInvoiced)
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('getBaseCustomerBalanceAmount')
            ->willReturn($baseCustomerBalAmountUsed);
        $this->orderMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);
        $this->orderMock->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->event->setOrder($this->orderMock);

        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($this->store);
        $this->store->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->balance->expects($this->any())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('setWebsiteId')
            ->with($websiteId)
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('setAmountDelta')
            ->with($baseCustomerBalAmountUsed)
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('setHistoryAction')
            ->with(History::ACTION_REVERTED)
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('setOrder')
            ->with($this->orderMock)
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('save')
            ->willReturnSelf();

        $this->model->execute($this->orderMock);
    }

    /**
     * Data provider for revert store credit for order
     *
     * @return array
     */
    public function revertStoreCreditForOrderDataProvider(): array
    {
        return [
            'execute revertOrder with invalid customerId' => [ 10.00, 10.00, 1, 1, null ],
            'execute revertOrder with invalid customer balance' => [ null, 10.00, 1, 1, 1 ],
            'execute revertOrder with valid order' => [ 10.00, 10.00, 1, 1, 1 ]
        ];
    }
}
