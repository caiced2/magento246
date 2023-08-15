<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Model\Reporting\Collectors;

use GuzzleHttp\Promise\PromiseInterface;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\Filters;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\DataCollectorInterface;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\ReportData;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\ClientFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\Rest\Request;
use GuzzleHttp\RequestOptions;

class BoltOrders implements DataCollectorInterface
{
    private const REPORT_SECTION_ID = 'orders';
    private const API_REQUEST_URI = 'https://api-sandbox.bolt.com/v1/';
    private const API_REQUEST_PATH = 'analytics/merchant';
    private const METRICS = ["cart_abandonment_rate", "abandoned_carts", "total_orders", "average_checkout_time"];
    //TODO: Add cart_abandonment_percentage
    private const PAYMENT_QUICK_CHECKOUT_API_KEY = 'payment/quick_checkout/api_key';
    private const SCOPE_WEBSITE = 'website';
    public const ROOT_WEBSITE_ID = 0;

    /**
     * @var ClientFactory
     */
    private ClientFactory $clientFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     *
     * @param ClientFactory $clientFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Returns an empty list of reports
     *
     * @param Filters $filters
     * @return ReportData
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(Filters $filters): ReportData
    {
        $startDateTimestamp = strtotime($filters->getStartDate());
        $endDateTimestamp = strtotime($filters->getEndDate());

        try {
            $requests = [
                'bolt' => $this->asyncRequest('bolt', $startDateTimestamp, $endDateTimestamp),
                'merchant' => $this->asyncRequest('merchant', $startDateTimestamp, $endDateTimestamp),
                'guest' => $this->asyncRequest('guest', $startDateTimestamp, $endDateTimestamp),
            ];

            $responses = Promise\settle($requests)->wait();

            $result = array_merge(
                $this->getResultFromResponse($responses, 'bolt'),
                $this->getResultFromResponse($responses, 'merchant'),
                $this->getResultFromResponse($responses, 'guest'),
            );

            return new ReportData(self::REPORT_SECTION_ID, $result);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return new ReportData(self::REPORT_SECTION_ID, []);
        }
    }

    /**
     * Do API request with provided params
     *
     * @param string $shopperType
     * @param int $startDate
     * @param int $endDate
     * @return PromiseInterface
     */
    private function asyncRequest(
        string $shopperType,
        int $startDate,
        int $endDate
    ): Promise\PromiseInterface {
        /** @var Client $client */
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => self::API_REQUEST_URI
        ]]);
        $params = [
            'headers' => [
                'X-Api-Key' => $this->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            RequestOptions::JSON => [
                "metrics" => self::METRICS,
                "start_date" =>  $startDate,
                "end_date" => $endDate,
                "shopper_type" => $shopperType,
            ]
        ];

        return  $client->requestAsync(
            Request::HTTP_METHOD_POST,
            self::API_REQUEST_PATH,
            $params
        );
    }

    /**
     * Get the api key from scope config
     *
     * @return string
     */
    private function getApiKey() : string
    {
        return $this->scopeConfig->getValue(
            self::PAYMENT_QUICK_CHECKOUT_API_KEY,
            self::SCOPE_WEBSITE,
            self::ROOT_WEBSITE_ID
        );
    }

    /**
     * Get array result from the response
     *
     * @param array $responses
     * @param string $shopperType
     * @return array
     */
    public function getResultFromResponse(array $responses, string $shopperType): array
    {
        $response = $responses[$shopperType];
        if ($response['state'] !== Promise\Promise::FULFILLED) {
            $this->logger->error($response['reason']);
            return [];
        }
        if (isset($response['value'])) {
            $contents = $response['value']->getBody()->getContents();
            try {
                $contentArray = \Safe\json_decode($contents, true);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                return [];
            }
            if (isset($contentArray['result']) && is_array($contentArray['result'])) {
                return array_map(
                    function ($order) use ($shopperType) {
                        $order['shopper_type'] = $shopperType;
                        return $order;
                    },
                    $contentArray['result']
                );
            }
        }
        return [];
    }
}
