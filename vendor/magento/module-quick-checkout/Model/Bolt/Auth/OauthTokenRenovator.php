<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Bolt\Auth;

use Magento\Framework\App\Request\Http;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\QuickCheckout\Model\Config;

/**
 * Renovates an access token using the refresh token and the scope
 */
class OauthTokenRenovator
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var TransferFactoryInterface
     */
    private TransferFactoryInterface $transferFactory;

    /**
     * @var ClientInterface
     */
    private ClientInterface $serviceClient;

    /**
     * @param Config $config
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $serviceClient
     */
    public function __construct(
        Config $config,
        TransferFactoryInterface $transferFactory,
        ClientInterface $serviceClient
    ) {
        $this->config = $config;
        $this->transferFactory = $transferFactory;
        $this->serviceClient = $serviceClient;
    }

    /**
     * Obtains a new access token using the provided refresh token and scope
     *
     * @param string $token
     * @param string $scope
     * @return OauthToken
     * @throws OauthException
     * @throws ClientException
     * @throws ConverterException
     */
    public function refresh(string $token, string $scope): OauthToken
    {
        $request = $this->prepareRefreshRequest($token, $scope);
        $response = $this->serviceClient->placeRequest($request);
        $this->validateResponse($response);

        $expiresAt = strtotime('now') + (int)$response['expires_in'];

        return new OauthToken(
            $response['access_token'],
            $response['scope'],
            $expiresAt,
            $response['refresh_token'],
            $response['refresh_token_scope']
        );
    }

    /**
     * Prepares the request to refresh the access token
     *
     * @param string $token
     * @param string $scope
     * @return TransferInterface
     */
    private function prepareRefreshRequest(string $token, string $scope): TransferInterface
    {
        $requestConfig = [
            'uri' => '/v1/oauth/token',
            'method' => Http::METHOD_POST,
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $token,
                'client_id' => $this->config->getPublishableKey(),
                'scope' => $scope,
                'client_secret' => $this->config->getApiKey()
            ],
            'headers' => []
        ];

        return $this->transferFactory->create($requestConfig);
    }

    /**
     * Ensure the response contains all the required data
     *
     * @param array $response
     * @return void
     * @throws OauthException
     */
    private function validateResponse(array $response): void
    {
        if (empty($response['access_token']) ||
            empty($response['scope']) ||
            empty($response['expires_in']) ||
            empty($response['refresh_token']) ||
            empty($response['refresh_token_scope'])) {
            throw new OauthException('Invalid Oauth response');
        }
    }
}
