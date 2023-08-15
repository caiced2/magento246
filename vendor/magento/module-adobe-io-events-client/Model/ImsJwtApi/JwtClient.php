<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\ImsJwtApi;

use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Jwt\JwkFactory;
use Magento\Framework\Jwt\Jws\JwsSignatureJwks;
use Magento\Framework\Jwt\JwtManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\JwtFrameworkAdapter\Model\JwsFactory;
use Magento\Store\Model\ScopeInterface;
use Throwable;

/**
 * Interaction with the Adobe IMS JWT API
 */
class JwtClient
{
    private const XML_PATH_IMS_JWT_EXPIRATION_INTERVAL =
        AdobeIOConfigurationProvider::XML_PATH_IMS_JWT_EXPIRATION_INTERVAL;
    private const XML_ADOBE_IO_PATH_ENVIRONMENT = AdobeIOConfigurationProvider::XML_ADOBE_IO_PATH_ENVIRONMENT;

    private const ENV_STAGING = AdobeIOConfigurationProvider::ENV_STAGING;

    private const IMS_JWT_URL_PROD = 'https://adobeid-na1.services.adobe.com/ims/exchange/jwt';
    private const IMS_JWT_URL_STAGE = 'https://adobeid-na1-stg1.services.adobe.com/ims/exchange/jwt';
    private const IMS_BASE_URL_JWT_TOKEN_PROD = 'https://ims-na1.adobelogin.com';
    private const IMS_BASE_URL_JWT_TOKEN_STAGE = 'https://ims-na1-stg1.adobelogin.com';

    /**
     * @var JwtManagerInterface
     */
    private JwtManagerInterface $jwtManager;

    /**
     * @var JwkFactory
     */
    private JwkFactory $jwkFactory;

    /**
     * @var JwsFactory
     */
    private JwsFactory $jwsFactory;

    /**
     * @var CurlFactory
     */
    private CurlFactory $curlFactory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var AdobeIOConfigurationProvider
     */
    private AdobeIOConfigurationProvider $configurationProvider;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @param JwtManagerInterface $jwtManager
     * @param JwkFactory $jwkFactory
     * @param JwsFactory $jwsFactory
     * @param CurlFactory $curlFactory
     * @param Json $json
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param DateTime $dateTime
     */
    public function __construct(
        JwtManagerInterface $jwtManager,
        JwkFactory $jwkFactory,
        JwsFactory $jwsFactory,
        CurlFactory $curlFactory,
        Json $json,
        AdobeIOConfigurationProvider $configurationProvider,
        DateTime $dateTime
    ) {
        $this->jwtManager = $jwtManager;
        $this->jwkFactory = $jwkFactory;
        $this->jwsFactory = $jwsFactory;
        $this->curlFactory = $curlFactory;
        $this->json = $json;
        $this->configurationProvider = $configurationProvider;
        $this->dateTime = $dateTime;
    }

    /**
     * Fetches the JWT token from the Adobe IMS API
     *
     * @return array|bool|float|int|mixed|string|null
     * @throws InvalidConfigurationException
     */
    public function fetchJwtTokenResponse()
    {
        try {
            $privateKey = $this->configurationProvider->getPrivateKey();
            $configuration = $this->configurationProvider->getConfiguration();
        } catch (NotFoundException $e) {
            throw new InvalidConfigurationException(__($e->getMessage()), $e);
        }
        $firstCredentials = $configuration->getFirstCredential();
        $imsOrgId = $configuration->getProject()->getOrganization()->getImsOrgId();
        $scopeEnv = $this->configurationProvider->getScopeConfig(self::XML_ADOBE_IO_PATH_ENVIRONMENT);
        $jwtUrl = $scopeEnv === self::ENV_STAGING
            ? self::IMS_JWT_URL_STAGE
            : self::IMS_JWT_URL_PROD;
        $baseTokenUrl = $scopeEnv === self::ENV_STAGING
            ? self::IMS_BASE_URL_JWT_TOKEN_STAGE
            : self::IMS_BASE_URL_JWT_TOKEN_PROD;

        $expirationTimestamp = $this->dateTime->timestamp() + $this->configurationProvider->getScopeConfig(
            self::XML_PATH_IMS_JWT_EXPIRATION_INTERVAL,
            ScopeInterface::SCOPE_STORE
        );
        try {
            $jwk = $this->jwkFactory->createSignRs256($privateKey->getData(), null);
        } catch (Throwable $e) {
            throw new InvalidConfigurationException(
                __('Service Account Private Key is invalid. Error: %1', $e->getMessage())
            );
        }
        $encSettings = new JwsSignatureJwks($jwk);

        $payload = [
            "exp" => $expirationTimestamp,
            "iss" => $imsOrgId,
            "sub" => $firstCredentials->getJwt()->getTechnicalAccountId(),
            "aud" => $baseTokenUrl . "/c/" . $firstCredentials->getJwt()->getClientId()
        ];

        foreach ($firstCredentials->getJwt()->getMetaScopes() as $metaScope) {
            $payload[$baseTokenUrl . "/s/" . $metaScope] = true;
        }

        $jws = $this->jwsFactory->create(
            [
                "alg" => "RS256",
            ],
            $this->json->serialize($payload),
            null
        );

        $token = $this->jwtManager->create($jws, $encSettings);

        $curl = $this->curlFactory->create();

        $curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $curl->addHeader('cache-control', 'no-cache');

        $curl->post(
            $jwtUrl,
            [
                'client_id' => $firstCredentials->getJwt()->getClientId(),
                'client_secret' => $firstCredentials->getJwt()->getClientSecret(),
                'jwt_token' => $token
            ]
        );

        return $this->json->unserialize($curl->getBody());
    }
}
