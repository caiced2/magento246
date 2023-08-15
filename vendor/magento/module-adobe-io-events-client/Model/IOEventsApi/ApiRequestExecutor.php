<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\IOEventsApi;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\Credentials\ScopeConfigCredentialsFactory;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Webapi\Rest\Request;

/**
 * Executes a request to the IOEventsApiClient including credentials
 */
class ApiRequestExecutor
{
    public const GET = Request::HTTP_METHOD_GET;
    public const POST = Request::HTTP_METHOD_POST;
    public const DELETE = Request::HTTP_METHOD_DELETE;

    /**
     * @var ResponseFactory
     */
    private ResponseFactory $responseFactory;

    /**
     * @var ClientFactory
     */
    private ClientFactory $clientFactory;

    /**
     * @var ScopeConfigCredentialsFactory
     */
    private ScopeConfigCredentialsFactory $credentialsFactory;

    /**
     * @param ResponseFactory $responseFactory
     * @param ClientFactory $clientFactory
     * @param ScopeConfigCredentialsFactory $credentialsFactory
     */
    public function __construct(
        ResponseFactory $responseFactory,
        ClientFactory $clientFactory,
        ScopeConfigCredentialsFactory $credentialsFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->clientFactory = $clientFactory;
        $this->credentialsFactory = $credentialsFactory;
    }

    /**
     * Executes a call to an API endpoint
     *
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return Response
     * @throws AuthorizationException
     * @throws InvalidConfigurationException
     * @throws NotFoundException
     */
    public function executeRequest(
        string $method,
        string $uri,
        array $params = []
    ): Response {
        $client = $this->clientFactory->create();
        $credentials = $this->credentialsFactory->create();

        if (!array_key_exists('headers', $params)) {
            $params['headers'] = [];
        }

        $params['headers']['x-api-key'] = $credentials->getClientId();
        $params['headers']['Authorization'] = 'Bearer ' . $credentials->getToken()->getAccessToken();

        try {
            $response = $client->request($method, $uri, $params);
        } catch (GuzzleException $exception) {
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => sprintf(
                    'Unsuccessful request: `%s %s` resulted in a `%s %s` response:',
                    $method,
                    $uri,
                    $exception->getResponse()->getStatusCode(),
                    $exception->getResponse()->getReasonPhrase()
                ) . PHP_EOL . $exception->getResponse()->getBody()->getContents()
            ]);
        }

        return $response;
    }
}
