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
 * Exchanges an access code for a token
 */
class OauthTokenResolver
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
     * Exchange an access code for an oauth token
     *
     * @param string $code
     * @return OauthToken
     * @throws OauthException
     * @throws ClientException
     * @throws ConverterException
     */
    public function exchange(string $code): OauthToken
    {
        $request = $this->prepareOauthRequest($code);
        $response = $this->serviceClient->placeRequest($request, false);
        $this->validateResponse($response);

        $expiresAt = strtotime('now') + (int)$response['expires_in'];

        return new OauthToken(
            $response['access_token'],
            $response['scope'],
            $expiresAt,
            $response['refresh_token'],
            $response['refresh_token_scope'],
            $response['id_token'],
        );
    }

    /**
     * Prepares the request to obtain the access token
     *
     * @param string $code
     * @return TransferInterface
     */
    private function prepareOauthRequest(string $code): TransferInterface
    {
        $requestConfig = [
            'uri' => '/v1/oauth/token',
            'method' => Http::METHOD_POST,
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->config->getPublishableKey(),
                'scope' => 'openid+bolt.account.manage',
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
            empty($response['refresh_token_scope']) ||
            empty($response['id_token'])) {
            throw new OauthException('Invalid Oauth response');
        }
    }
}
