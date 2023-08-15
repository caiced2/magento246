<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Block\Checkout\Onepage\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\CustomerBalance\Block\Checkout\Onepage\Payment\Additional;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\Method\Free;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\CustomerBalance\Block\Checkout\Onepage\Payment\Additional
 */
class AdditionalTest extends TestCase
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var BalanceFactory
     */
    private $balanceFactoryMock;

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerMock;

    /**
     * @var Balance
     */
    private $balanceModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeMock;

    /**
     * @var ScopeConfigInterface|MockObject;
     */
    private $scopeConfigMock;

    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var Session|MockObject
     */
    private $customerSessionMock;

    /**
     * @var Customer|MockObject
     */
    private $customerMock;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * initialize arguments for construct
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->balanceModel = $this->getMockBuilder(Balance::class)
            ->addMethods(['setCustomerId', 'setWebsiteId'])
            ->onlyMethods(['getAmount', 'loadByCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->balanceFactoryMock = $this->createPartialMock(
            BalanceFactory::class,
            ['create']
        );
        $this->contextMock = $this->createMock(Context::class);
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->customerSessionMock = $this->createMock(Session::class);
        $this->customerMock = $this->createMock(Customer::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->storeManagerMock
            ->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(rand(1, 10));
        $this->customerSessionMock->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customerMock);
        $this->customerMock->expects($this->any())
            ->method('getId')
            ->willReturn(rand(1, 1000));
        $this->balanceFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->balanceModel);
        $this->balanceModel
            ->expects($this->any())
            ->method('setCustomerId')
            ->willReturn($this->balanceModel);
        $this->balanceModel
            ->expects($this->any())
            ->method('setWebsiteId')
            ->willReturn($this->balanceModel);
        $this->balanceModel
            ->expects($this->any())
            ->method('loadByCustomer')
            ->willReturn($this->balanceModel);
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->addMethods(
                [
                    'getCustomerId',
                    'getBaseGrandTotal',
                    'getBaseCustomerBalAmountUsed',
                    'getUseCustomerBalance',
                    'getCustomerBalanceAmountUsed'
                ]
            )
            ->onlyMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock
            ->expects($this->any())
            ->method('getCustomerId')
            ->willReturn(true);
        $quoteMock
            ->expects($this->any())
            ->method('getStoreId')
            ->willReturn(true);
        $quoteMock
            ->expects($this->any())
            ->method('getBaseGrandTotal')
            ->willReturn(1000.0000);
        $quoteMock
            ->expects($this->any())
            ->method('getBaseCustomerBalAmountUsed')
            ->willReturn(1000.0000);
        $quoteMock
            ->expects($this->any())
            ->method('getCustomerBalanceAmountUsed')
            ->willReturn(1000.0000);
        $quoteMock
            ->expects($this->any())
            ->method('getUseCustomerBalance')
            ->willReturn(true);
        $this->checkoutSessionMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->scopeConfigMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getStoreManager')
            ->willReturn($this->storeManagerMock);

        $this->className = new Additional(
            $this->contextMock,
            $this->balanceFactoryMock,
            $this->checkoutSessionMock,
            $this->customerSessionMock
        );
    }

    /**
     * Test isAllowed method
     * @return void
     *
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed(bool $config, float $balance, bool $result)
    {
        $this->scopeConfigMock
            ->expects($this->once())
            ->method('isSetFlag')
            ->with(Free::XML_PATH_PAYMENT_FREE_ACTIVE)
            ->willReturn($config);

        if ($config) {

            $this->balanceModel
                ->expects($this->any())
                ->method('getAmount')
                ->willReturn($balance);

        }

        $this->assertEquals($result, $this->className->isAllowed());
    }

    /**
     * Data provider with array in param values.
     *
     * @return array
     */
    public function isAllowedDataProvider(): array
    {
        return [
            'free_payment_disabled' => [
                'config' => false,
                'balance' => 1000.0000,
                'result' => false
            ],
            'free_payment_enabled_no_balance' => [
                'config' => true,
                'balance' => 0.0000,
                'result' => false
            ],
            'free_payment_enabled_with_balance' => [
                'config' => true,
                'balance' => 3000.0000,
                'result' => true
            ]
        ];
    }
}
