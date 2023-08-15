<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Model;

use Magento\Customer\Model\Session;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Model\ConfigProvider;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Pricing\Helper\Data;

class ConfigProviderTest extends TestCase
{
    /**
     * @var ConfigProvider
     */
    protected $model;

    /**
     * @var Session|MockObject
     */
    protected $customerSession;

    /**
     * @var Store|MockObject
     */
    protected $store;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Checkout\Model\Session|MockObject
     */
    protected $checkoutSession;

    /**
     * @var \Magento\CustomerBalance\Model\Balance|MockObject
     */
    protected $balance;

    /**
     * @var BalanceFactory|MockObject
     */
    protected $balanceFactory;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilder;

    /**
     * @var Quote|MockObject
     */
    protected $quote;

    /**
     * @var Data
     */
    protected $pricingHelper;

    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);

        $this->checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->balance = $this->getMockBuilder(Balance::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCustomerId',
                'setWebsiteId',
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

        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getUseCustomerBalance',
                'getBaseCustomerBalAmountUsed'
            ])
            ->getMock();

        $this->pricingHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new ConfigProvider(
            $this->customerSession,
            $this->storeManager,
            $this->checkoutSession,
            $this->balanceFactory,
            $this->urlBuilder,
            $this->pricingHelper
        );
    }

    /**
     * @param int $customerId
     * @param int $websiteId
     * @param int $useCustomerBalance
     * @param float $baseCustomerBalAmountUsed
     * @param float $balanceAmount
     * @param bool $isAvailable
     * @param bool $amountSubstracted
     * @dataProvider providerGetConfig
     */
    public function testGetConfig(
        $customerId,
        $websiteId,
        $useCustomerBalance,
        $baseCustomerBalAmountUsed,
        $balanceAmount,
        $isAvailable,
        $amountSubstracted
    ) {
        $removeUrl = 'http://example.com/customerBalance/remove';
        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->quote->expects($this->once())
            ->method('getUseCustomerBalance')
            ->willReturn($useCustomerBalance);
        $this->quote->expects($this->once())
            ->method('getBaseCustomerBalAmountUsed')
            ->willReturn($baseCustomerBalAmountUsed);

        $this->checkoutSession->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);

        $this->balance->expects($this->any())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('setWebsiteId')
            ->with($websiteId)
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('loadByCustomer')
            ->willReturnSelf();
        $this->balance->expects($this->any())
            ->method('getAmount')
            ->willReturn($balanceAmount);
        $this->pricingHelper->expects($this->any())
            ->method('currency')
            ->willReturn($balanceAmount);

        $this->store->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->urlBuilder
            ->expects($this->once())
            ->method('getUrl')
            ->with('magento_customerbalance/cart/remove')
            ->willReturn($removeUrl);

        $expected = [
            'payment' => [
                'customerBalance' => [
                    'isAvailable' => $isAvailable,
                    'amountSubstracted' => $amountSubstracted,
                    'usedAmount' => $baseCustomerBalAmountUsed,
                    'balance' => $balanceAmount,
                    'balanceRemoveUrl' => $removeUrl
                ],
            ]
        ];

        $result = $this->model->getConfig();
        $this->assertEquals($expected, $result);
    }

    /**
     * 1. Customer ID
     * 2. Website ID
     * 3. Use Customer Balance flag
     * 4. Used Customer Balance Amount
     * 5. Customer Balance Amount
     * 6. Is Customer Balance Available (RESULT)
     * 7. Is Customer Balance Amount Substracted (RESULT)
     *
     * @return array
     */
    public function providerGetConfig()
    {
        return [
            [0, 0, 0, 0, 0, false, false],
            [1, 1, 0, 0, 0, false, false],
            [1, 1, 1, 5., 10., true, true],
        ];
    }
}
