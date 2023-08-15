<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Test\Unit\Model\Reporting\Collectors;

use GuzzleHttp\Psr7\Response;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\Collectors\BoltOrders;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\Filters;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class BoltOrdersTest extends TestCase
{
    /**
     * @var ClientFactory|ClientFactory&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private ClientFactory $clientFactory;

    /**
     * @var ScopeConfigInterface|ScopeConfigInterface&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject|LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var Client|Client&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private Client $client;

    /**
     * @var BoltOrders
     */
    private BoltOrders $boltOrders;

    /**
     * @return array
     */
    public function getValueFunctionCallArguments(): array
    {
        return ['payment/quick_checkout/api_key', 'website', BoltOrders::ROOT_WEBSITE_ID];
    }

    /**
     * @return array
     */
    public function createFunctionCallArguments(): array
    {
        return [
            [
                'config' => ['base_uri' => 'https://api-sandbox.bolt.com/v1/']
            ]
        ];
    }

    /**
     * @return array
     */
    public function requestAsyncFunctionCallArguments(): array
    {
        return ['POST', 'analytics/merchant', self::anything()];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientFactory = $this->createMock(ClientFactory::class);
        $this->client = $this->createMock(Client::class);

        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->boltOrders = new BoltOrders(
            $this->clientFactory,
            $this->scopeConfig,
            $this->logger,
        );
    }

    public function testRequestOk()
    {
        $boltApiResponse =
        '{
        "result":
            [
                {
                "date":1664582400,
                "cart_abandonment_rate":0.232,
                "abandoned_carts":123,
                "average_checkout_time":310,
                "total_orders":275
                }
            ]
        }';

        $expectedResult = [
            [
                'date' => 1664582400,
                'cart_abandonment_rate' => 0.232,
                'average_checkout_time' => 310,
                'total_orders' => 275,
                'shopper_type' => 'bolt',
                'abandoned_carts' => 123

            ],
            [
                'date' => 1664582400,
                'cart_abandonment_rate' => 0.232,
                'average_checkout_time' => 310,
                'total_orders' => 275,
                'shopper_type' => 'merchant',
                'abandoned_carts' => 123
            ],
            [
                'date' => 1664582400,
                'cart_abandonment_rate' => 0.232,
                'average_checkout_time' => 310,
                'total_orders' => 275,
                'shopper_type' => 'guest',
                'abandoned_carts' => 123
            ]
        ];

        $this->clientFactory->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(
                $this->createFunctionCallArguments(),
                $this->createFunctionCallArguments(),
                $this->createFunctionCallArguments(),
            )
            ->willReturn($this->client);

        $this->scopeConfig->expects($this->exactly(3))
            ->method('getValue')
            ->withConsecutive(
                $this->getValueFunctionCallArguments(),
                $this->getValueFunctionCallArguments(),
                $this->getValueFunctionCallArguments()
            )
            ->willReturn('MY_API_KEY');

        $promiseBoltRequest = new Promise\FulfilledPromise(
            new Response(200, [], $boltApiResponse)
        );

        $promiseMerchantRequest = new Promise\FulfilledPromise(
            new Response(200, [], $boltApiResponse)
        );

        $promiseGuestRequest = new Promise\FulfilledPromise(
            new Response(200, [], $boltApiResponse)
        );

        $this->client->expects($this->exactly(3))
            ->method('requestAsync')
            ->withConsecutive(
                $this->requestAsyncFunctionCallArguments(),
                $this->requestAsyncFunctionCallArguments(),
                $this->requestAsyncFunctionCallArguments(),
            )
            ->willReturnOnConsecutiveCalls($promiseBoltRequest, $promiseMerchantRequest, $promiseGuestRequest);

        $result = $this->boltOrders->collect($this->prepareFilters());

        $this->assertEquals('orders', $result->getSection());
        $this->assertEquals(
            $expectedResult,
            $result->getContent()
        );
    }

    /**
     * @return Filters
     */
    private function prepareFilters(): Filters
    {
        return new Filters(
            '2022-08-07',
            '2022-10-14',
            1,
            'all'
        );
    }
}
