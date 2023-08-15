<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Magento\AdobeCommerceEventsClient\Event\Config as EventsConfig;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException as AdobeIOConfigurationException;
use Magento\AdobeIoEventsClient\Model\Credentials\ScopeConfigCredentialsFactory;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;

/**
 * Client class for Commerce Events
 */
class Client
{
    /**
     * @param Config $config
     * @param ClientFactory $clientFactory
     * @param ScopeConfigCredentialsFactory $credentialsFactory
     */
    public function __construct(
        private EventsConfig $config,
        private ClientFactory $clientFactory,
        private ScopeConfigCredentialsFactory $credentialsFactory
    ) {
    }

    /**
     * Sends a batch of event data to the Events Service.
     *
     * @param array $messages
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws InvalidConfigurationException
     */
    public function sendEventDataBatch(array $messages): ResponseInterface
    {
        $url = sprintf(
            '%s/v1/publish-batch',
            $this->config->getEndpointUrl(),
        );

        try {
            return $this->doRequest('POST', $url, [
                'http_errors' => false,
                RequestOptions::JSON => [
                    'merchantId' => $this->config->getMerchantId(),
                    'environmentId' => $this->config->getEnvironmentId(),
                    'messages' => $messages,
                    'instanceId' => $this->config->getInstanceId()
                ]
            ]);
        } catch (AuthorizationException|NotFoundException $exception) {
            throw new InvalidConfigurationException(__($exception->getMessage()));
        }
    }

    /**
     * Makes request to the provided url.
     *
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return ResponseInterface
     * @throws AuthorizationException
     * @throws GuzzleException
     * @throws InvalidConfigurationException
     * @throws NotFoundException
     */
    private function doRequest(
        string $method,
        string $uri,
        array $params = []
    ): ResponseInterface {
        $credentials = $this->credentialsFactory->create();
        try {
            $params['headers']['Authorization'] = 'Bearer ' . $credentials->getToken()->getAccessToken();
            $params['headers']['x-api-key'] = $credentials->getClientId();
            $params['headers']['x-ims-org-id'] = $credentials->getImsOrgId();
        } catch (AdobeIOConfigurationException $exception) {
            throw new InvalidConfigurationException(__($exception->getMessage()));
        }

        /** @var GuzzleHttpClient $client */
        $client = $this->clientFactory->create();
        return $client->request($method, $uri, $params);
    }
}
