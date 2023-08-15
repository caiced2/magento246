<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model;

use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\QuickCheckout\Gateway\Http\TransferFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Service to configure callback URL
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CallbackUrlService
{
    private const CALLBACK_URL = '%s/rest/%s/V1/quick-checkout/storefront/has-account';
    private const PUBLISHABLE_KEY_SEGMENTS_COUNT = 3;
    private const DIVISION_ID_SEGMENT = 1;

    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var ClientInterface
     */
    private $serviceClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @param TransferFactory $transferFactory
     * @param ClientInterface $serviceClient
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        TransferFactory $transferFactory,
        ClientInterface $serviceClient,
        Config $config,
        ScopeConfigInterface $scopeConfig,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->transferFactory = $transferFactory;
        $this->serviceClient = $serviceClient;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * Update callback URL
     *
     * @param string $websiteId
     * @return void
     * @throws ClientException
     * @throws ConverterException
     * @throws NoSuchEntityException
     */
    public function update(string $websiteId)
    {
        $website = $this->websiteRepository->getById($websiteId);

        $defaultStore = $website->getDefaultStore();

        $divisionId = $this->getDivisionId($website->getCode());

        $url = $this->getCallbackUrl($defaultStore->getCode());

        $this->updateCallbackUrl($divisionId, $url, $website->getCode());
    }

    /**
     * Update callback URL
     *
     * @param string $divisionId
     * @param string $url
     * @param string $websiteCode
     * @return array
     * @throws ClientException
     * @throws ConverterException
     * @throws InvalidArgumentException
     */
    private function updateCallbackUrl(string $divisionId, string $url, string $websiteCode) : array
    {
        $request = [
            'uri' => '/v1/merchant/callbacks',
            'method' => Http::METHOD_POST,
            'body' => [
                'division_id' => $divisionId,
                'callback_urls' => [
                    [
                        'type' => 'get_account',
                        'url' => $url
                    ]
                ]
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'bearer ' . $this->config->getApiKey($websiteCode),
            ]
        ];
        $transferObject = $this->transferFactory->create($request);
        return $this->serviceClient->placeRequest($transferObject);
    }

    /**
     * Get callback URL
     *
     * @param string $storeCode
     * @return string
     */
    private function getCallbackUrl(string $storeCode) : string
    {
        $canUseHttps = $this->scopeConfig->getValue(
            Store::XML_PATH_SECURE_IN_FRONTEND,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
        $baseUrl = $this->scopeConfig->getValue(
            $canUseHttps ? Store::XML_PATH_SECURE_BASE_URL : Store::XML_PATH_UNSECURE_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );

        return sprintf(self::CALLBACK_URL, $baseUrl, $storeCode);
    }

    /**
     * Get merchant division of a website
     *
     * @param string $websiteCode
     * @return string
     */
    private function getDivisionId(string $websiteCode): string
    {
        $publishableKeyParts = explode('.', $this->config->getPublishableKey($websiteCode));

        $this->assertPublishableKeyIsValid($publishableKeyParts);

        return $publishableKeyParts[self::DIVISION_ID_SEGMENT];
    }

    /**
     * Assert that the format of the provided publishable key is valid
     *
     * @param array $parts
     * @return void
     */
    private function assertPublishableKeyIsValid(array $parts): void
    {
        if (count($parts) !== self::PUBLISHABLE_KEY_SEGMENTS_COUNT) {
            throw new InvalidArgumentException('Invalid publishable key');
        }
    }
}
