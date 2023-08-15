<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Model\Total\Quote;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\CustomerBalance\Helper\Data;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Model\Total\Quote\Customerbalance;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerbalanceTest extends TestCase
{
    /** @var Customerbalance */
    protected $customerBalance;

    /** @var StoreManagerInterface|MockObject */
    protected $storeManager;

    /** @var BalanceFactory|MockObject */
    protected $balanceFactory;

    /** @var PriceCurrencyInterface|MockObject */
    protected $priceCurrency;

    /** @var Data|MockObject */
    protected $customerBalanceData;

    /** @var Quote|MockObject */
    protected $quote;

    /** @var ShippingAssignmentInterface|MockObject */
    protected $shippingAssignment;

    /** @var Total|MockObject */
    protected $total;

    /** @var ShippingInterface|MockObject */
    protected $shipping;

    /** @var AddressInterface|MockObject */
    protected $address;

    /** @var CustomerInterface|MockObject */
    protected $customer;

    /** @var StoreInterface|MockObject */
    protected $store;

    /** @var Balance|MockObject */
    protected $balance;

    public function setUpCustomerBalance()
    {
        $this->storeManager = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->balanceFactory = $this->createPartialMock(
            BalanceFactory::class,
            ['create']
        );
        $this->priceCurrency = $this->getMockForAbstractClass(
            PriceCurrencyInterface::class,
            [],
            '',
            false
        );
        $this->customerBalanceData = $this->createMock(Data::class);

        $this->customerBalance = new Customerbalance(
            $this->storeManager,
            $this->balanceFactory,
            $this->customerBalanceData,
            $this->priceCurrency
        );
    }

    protected function setUp(): void
    {
        $this->setUpCustomerBalance();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->addMethods(
                [
                    'setBaseCustomerBalAmountUsed',
                    'setCustomerBalanceAmountUsed',
                    'getBaseCustomerBalAmountUsed',
                    'getCustomerBalanceAmountUsed',
                    'getUseCustomerBalance'
                ]
            )
            ->onlyMethods(['getCustomer', 'getStoreId', 'isVirtual', 'getStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->total = $this->getMockBuilder(Total::class)
            ->addMethods(
                [
                    'getCustomerBalanceAmount',
                    'setBaseCustomerBalanceAmount',
                    'setCustomerBalanceAmount',
                    'getBaseGrandTotal',
                    'getGrandTotal',
                    'setBaseGrandTotal',
                    'setGrandTotal'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingAssignment = $this->getMockForAbstractClass(
            ShippingAssignmentInterface::class,
            [],
            '',
            false
        );
        $this->shipping = $this->getMockForAbstractClass(
            ShippingInterface::class,
            [],
            '',
            false
        );
        $this->address = $this->getMockForAbstractClass(
            AddressInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getAddressType']
        );
        $this->customer = $this->getMockForAbstractClass(
            CustomerInterface::class,
            [],
            '',
            false
        );
        $this->store = $this->getMockForAbstractClass(StoreInterface::class, [], '', false);
        $this->balance = $this->getMockBuilder(Balance::class)
            ->addMethods(['setCustomer', 'setCustomerId', 'setWebsiteId'])
            ->onlyMethods(['loadByCustomer', 'getAmount'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCollectWithDisabledCustomerBalance()
    {
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->assertSame(
            $this->customerBalance,
            $this->customerBalance->collect(
                $this->quote,
                $this->shippingAssignment,
                $this->total
            )
        );
    }

    public function testCollectIfQuoteIsVirtual()
    {
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->shippingAssignment->expects($this->once())
            ->method('getShipping')
            ->willReturn($this->shipping);
        $this->shipping->expects($this->once())
            ->method('getAddress')
            ->willReturn($this->address);
        $this->address->expects($this->once())
            ->method('getAddressType')
            ->willReturn(AbstractAddress::TYPE_SHIPPING);
        $this->quote->expects($this->once())
            ->method('isVirtual')
            ->willReturn(true);

        $this->assertSame(
            $this->customerBalance,
            $this->customerBalance->collect(
                $this->quote,
                $this->shippingAssignment,
                $this->total
            )
        );
    }

    protected function loadCustomerBalanceAmount()
    {
        $customerId = 4;
        $storeId = 1;
        $websiteId = 2;

        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->shippingAssignment->expects($this->once())
            ->method('getShipping')
            ->willReturn($this->shipping);
        $this->shipping->expects($this->once())
            ->method('getAddress')
            ->willReturn($this->address);
        $this->address->expects($this->once())
            ->method('getAddressType')
            ->willReturn(AbstractAddress::TYPE_BILLING);
        $this->quote->expects($this->never())
            ->method('isVirtual')
            ->willReturn(true);
        $this->quote->expects($this->atLeastOnce())
            ->method('setBaseCustomerBalAmountUsed');
        $this->quote->expects($this->atLeastOnce())
            ->method('setCustomerBalanceAmountUsed');
        $this->quote->expects($this->exactly(3))
            ->method('getCustomer')
            ->willReturn($this->customer);
        $this->quote->expects($this->once())
            ->method('getUseCustomerBalance')
            ->willReturn(true);
        $this->quote->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);
        $this->quote->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->customer->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($customerId);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($this->store);

        $this->balanceFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->balance);

        $this->balance->expects($this->once())
            ->method('setCustomer')
            ->with($this->customer)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();

        $this->balance->expects($this->once())
            ->method('setWebsiteId')
            ->with($websiteId)
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('loadByCustomer')
            ->willReturnSelf();
        $this->balance->expects($this->once())
            ->method('getAmount')
            ->willReturn(100);
        $this->store->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->priceCurrency->expects($this->once())
            ->method('convert')
            ->with(100, $this->store)
            ->willReturnArgument(0);
    }

    public function testCollect()
    {
        $this->loadCustomerBalanceAmount();

        $this->quote->expects($this->exactly(2))
            ->method('getBaseCustomerBalAmountUsed')
            ->willReturn(50);
        $this->quote->expects($this->exactly(2))
            ->method('getCustomerBalanceAmountUsed')
            ->willReturn(50);

        $this->total->expects($this->exactly(2))
            ->method('getBaseGrandTotal')
            ->willReturn(50);
        $this->total->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn(50);
        $this->total->expects($this->once())
            ->method('setBaseGrandTotal')
            ->with(0);
        $this->total->expects($this->once())
            ->method('setGrandTotal')
            ->with(0);
        $this->total->expects($this->once())
            ->method('setBaseCustomerBalanceAmount')
            ->with(50);
        $this->total->expects($this->once())
            ->method('setCustomerBalanceAmount')
            ->with(50);

        $this->customerBalance->collect(
            $this->quote,
            $this->shippingAssignment,
            $this->total
        );
    }

    public function testCollectWithInsufficientlyOfStoreCredits()
    {
        $this->loadCustomerBalanceAmount();

        $this->quote->expects($this->exactly(2))
            ->method('getBaseCustomerBalAmountUsed')
            ->willReturn(50);
        $this->quote->expects($this->exactly(2))
            ->method('getCustomerBalanceAmountUsed')
            ->willReturn(50);

        $this->total->expects($this->exactly(2))
            ->method('getBaseGrandTotal')
            ->willReturn(100);
        $this->total->expects($this->once())
            ->method('getGrandTotal')
            ->willReturn(100);
        $this->total->expects($this->once())
            ->method('setBaseGrandTotal')
            ->with(50);
        $this->total->expects($this->once())
            ->method('setGrandTotal')
            ->with(50);
        $this->total->expects($this->once())
            ->method('setBaseCustomerBalanceAmount')
            ->with(50);
        $this->total->expects($this->once())
            ->method('setCustomerBalanceAmount')
            ->with(50);

        $this->customerBalance->collect(
            $this->quote,
            $this->shippingAssignment,
            $this->total
        );
    }

    public function testFetch()
    {
        $code = 'customerbalance';
        $this->customerBalance->setCode($code);
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->total->expects($this->exactly(2))
            ->method('getCustomerBalanceAmount')
            ->willReturn(50);
        $this->assertEquals(
            [
                'code' => $code,
                'title' => __('Store Credit'),
                'value' => -50
            ],
            $this->customerBalance->fetch($this->quote, $this->total)
        );
    }

    public function testFetchWithDisabledCustomerBalance()
    {
        $this->customerBalanceData->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->assertNull($this->customerBalance->fetch($this->quote, $this->total));
    }
}
