<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Gateway\Http;

use GuzzleHttp\ClientFactory;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\QuickCheckout\Model\Config;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Framework\DataObject\IdentityService;
use Magento\Framework\App\Request\Http;
use Magento\QuickCheckout\Model\ErrorProcessor;
use Psr\Log\LoggerInterface;

class ServiceClient implements ClientInterface
{
    /**
     * @var string
     */
    private const API_KEY_HEADER = 'X-API-Key';

    /**
     * @var int[]
     */
    private const SUCCESSFUL_RESPONSE_CODES = [200, 201, 202, 204];

    /**
     * @var string[]
     */
    private const UNSAFE_HEADERS = [self::API_KEY_HEADER];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ClientFactory
     */
    private $guzzleClientFactory;

    /**
     * @var IdentityService
     */
    private $identityService;

    /**
     * @var Logger
     */
    private $paymentsLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ErrorProcessor
     */
    private $errorProcessor;

    /**
     * @param Config $config
     * @param ClientFactory $guzzleClientFactory
     * @param IdentityService $identityService
     * @param ErrorProcessor $errorProcessor
     * @param Logger $paymentsLogger
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        ClientFactory $guzzleClientFactory,
        IdentityService $identityService,
        ErrorProcessor $errorProcessor,
        Logger $paymentsLogger,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->guzzleClientFactory = $guzzleClientFactory;
        $this->identityService = $identityService;
        $this->errorProcessor = $errorProcessor;
        $this->paymentsLogger = $paymentsLogger;
        $this->logger = $logger;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @param bool $jsonEncode
     * @return array
     * @throws ClientException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function placeRequest(TransferInterface $transferObject, $jsonEncode = true)
    {
        $settings = [
            'http_errors' => false,
            'base_uri' => 'https://' . $this->config->getApiHost() . '/'
        ];

        $client = $this->guzzleClientFactory->create(['config' => $settings]);

        $transferObjectHeaders = $transferObject->getHeaders();

        $headers = array_merge(
            $transferObjectHeaders,
            ['X-Nonce' => $this->identityService->generateId(),],
        );

        if (empty($headers[self::API_KEY_HEADER])) {
            $headers[self::API_KEY_HEADER] = $this->config->getApiKey();
        }

        $options = [
            'headers' => $headers,
            'body' => $transferObject->getBody() == null ? '' : json_encode($transferObject->getBody())
        ];

        if (!$jsonEncode &&
            $transferObject->getMethod() === Http::METHOD_POST &&
            (!isset($headers['Content-Type']) ||
            ((isset($headers['Content-Type']) && $headers['Content-Type'] !== 'application/json')))
        ) {
            unset($options['body']);
            $options['form_params'] = $transferObject->getBody();
        }

        $response = $client->request($transferObject->getMethod(), $transferObject->getUri(), $options);
        $debugInfo = [
            'request' => [
                'uri' => $transferObject->getUri(),
                'headers' => json_encode($this->sanitizeHeaders($headers)),
                'method' => $transferObject->getMethod(),
                'body' => json_encode($transferObject->getBody()),
            ],
            'response' => [
                'code' => $response->getStatusCode(),
                'headers' => json_encode($response->getHeaders()),
                'body' => (string) $response->getBody()
            ]
        ];
        $this->paymentsLogger->debug($debugInfo);
        $responseBody = json_decode((string) $response->getBody(), true);
        if (!in_array($response->getStatusCode(), self::SUCCESSFUL_RESPONSE_CODES)) {
            $errorMessage = $this->errorProcessor->process($responseBody['errors'] ?? []);
            $this->logger->error(
                $errorMessage,
                $debugInfo
            );
            throw new ClientException(__($errorMessage));
        }
        return $responseBody;
    }

    /**
     * Sanitize headers
     *
     * @param array $headers
     * @return array
     */
    private function sanitizeHeaders(array $headers) : array
    {
        $result = $headers;
        foreach (self::UNSAFE_HEADERS as $unsafeHeader) {
            $result[$unsafeHeader] = '***';
        }
        return $result;
    }
}
