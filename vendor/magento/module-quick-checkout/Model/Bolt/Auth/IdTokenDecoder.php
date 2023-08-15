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

class IdTokenDecoder
{
    /**
     * @var TransferFactoryInterface
     */
    private TransferFactoryInterface $transferFactory;

    /**
     * @var ClientInterface
     */
    private ClientInterface $serviceClient;

    /**
     * @var JwtManagerInterface
     */
    private JwtManagerInterface $jwtManager;

    /**
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $serviceClient
     * @param JwtManagerInterface $jwtManager
     */
    public function __construct(
        TransferFactoryInterface $transferFactory,
        ClientInterface          $serviceClient,
        JwtManagerInterface      $jwtManager
    ) {
        $this->transferFactory = $transferFactory;
        $this->serviceClient = $serviceClient;
        $this->jwtManager = $jwtManager;
    }

    /**
     * Decodes the provided access token
     *
     * @param string $idToken
     * @return IdTokenPayload
     * @throws OauthException
     * @throws ClientException
     * @throws ConverterException
     */
    public function decode(string $idToken): IdTokenPayload
    {
        $openIdRequest = $this->prepareOpenIdRequest();
        $openIdConfig = $this->serviceClient->placeRequest($openIdRequest);
        $jwksUri = $this->extractJwksUri($openIdConfig);

        $jwksRequest = $this->prepareJwksRequest($jwksUri);
        $jwks = $this->serviceClient->placeRequest($jwksRequest);

        $payload = $this->jwtManager->decode($idToken, $jwks);
        $this->validatePayload($payload);

        return new IdTokenPayload($payload['email'], (bool)$payload['email_verified']);
    }

    /**
     * Prepares the request to obtain the open id configuration
     *
     * @return TransferInterface
     */
    private function prepareOpenIdRequest(): TransferInterface
    {
        $requestConfig = [
            'uri' => '/.well-known/openid-configuration',
            'method' => Http::METHOD_GET,
            'body' => [],
            'headers' => []
        ];

        return $this->transferFactory->create($requestConfig);
    }

    /**
     * Prepares the request to obtain the JSON web key sets
     *
     * @param string $uri
     * @return TransferInterface
     */
    private function prepareJwksRequest(string $uri): TransferInterface
    {
        $requestConfig = [
            'uri' => $uri,
            'method' => Http::METHOD_GET,
            'body' => [],
            'headers' => []
        ];

        return $this->transferFactory->create($requestConfig);
    }

    /**
     * Validates open id configuration and returns the jwks uri
     *
     * @param array $openIdConfig
     * @return string
     * @throws OauthException
     */
    public function extractJwksUri(array $openIdConfig): string
    {
        $jwksUri = $openIdConfig['jwks_uri'] ?? '';

        if (!filter_var($jwksUri, FILTER_VALIDATE_URL)) {
            throw new OauthException('Invalid open id config: invalid jwks uri');
        }

        // @codingStandardsIgnoreStart
        $path = parse_url($jwksUri, PHP_URL_PATH);
        // @codingStandardsIgnoreEnd

        if (empty($path)) {
            throw new OauthException('Invalid open id config: invalid jwks uri');
        }

        return $path;
    }

    /**
     * Validates the payload of the access token
     *
     * @param array $accessTokenPayload
     * @return void
     * @throws OauthException
     */
    public function validatePayload(array $accessTokenPayload): void
    {
        if (empty($accessTokenPayload['email']) || !isset($accessTokenPayload['email_verified'])) {
            throw new OauthException('Invalid access token payload: missing email or email verification');
        }
    }
}
