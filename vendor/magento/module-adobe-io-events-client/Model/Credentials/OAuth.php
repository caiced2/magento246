<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Credentials;

use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\AdobeImsApi\Api\Data\TokenResponseInterfaceFactory;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Credentials\Oauth\ApiUrlResolver;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\OAuth as OAuthCredentials;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Oauth based credentials
 */
class OAuth implements CredentialsInterface
{
    /**
     * @var TokenResponseInterface|null
     */
    private ?TokenResponseInterface $token = null;

    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param TokenResponseInterfaceFactory $tokenResponseFactory
     * @param CurlFactory $curlFactory
     * @param Json $json
     * @param ApiUrlResolver $apiUrlResolver
     */
    public function __construct(
        private AdobeIOConfigurationProvider $configurationProvider,
        private TokenResponseInterfaceFactory $tokenResponseFactory,
        private CurlFactory $curlFactory,
        private Json $json,
        private ApiUrlResolver $apiUrlResolver,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return $this->getOAuthCredentials()->getClientId();
    }

    /**
     * @inheritDoc
     */
    public function getImsOrgId(): string
    {
        $configuration = $this->getConfiguration();

        return $configuration->getProject()->getOrganization()->getImsOrgId();
    }

    /**
     * @inheritDoc
     */
    public function getToken(): TokenResponseInterface
    {
        if ($this->token === null) {
            /** @var Curl $curl */
            $curl = $this->curlFactory->create();
            $curl->addHeader('cache-control', 'no-cache');
            $curl->post(
                $this->apiUrlResolver->get(),
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->getClientId(),
                    'client_secret' => $this->getOAuthCredentials()->getClientSecret(),
                    'scope' => implode(',', $this->getOAuthCredentials()->getScopes())
                ]
            );

            $response = $this->json->unserialize($curl->getBody());

            if (!is_array($response) || empty($response['access_token'])) {
                throw new AuthorizationException(__('Could not login to Adobe IMS.'));
            }

            $this->token = $this->tokenResponseFactory->create(['data' => $response]);
        }

        return $this->token;
    }

    /**
     * Returns OAuth credentials.
     *
     * @return OAuthCredentials
     * @throws InvalidConfigurationException
     */
    private function getOAuthCredentials(): OAuthCredentials
    {
        $credentials = $this->getConfiguration()->getFirstCredential();
        if (!$credentials->getOAuth() instanceof OAuthCredentials) {
            throw new InvalidConfigurationException(
                __('OAuth credentials is not found in the Adobe I/O Workspace Configuration')
            );
        }

        return $credentials->getOAuth();
    }

    /**
     * Returns console configuration.
     *
     * @return AdobeConsoleConfiguration
     * @throws InvalidConfigurationException
     */
    private function getConfiguration(): AdobeConsoleConfiguration
    {
        try {
            return $this->configurationProvider->getConfiguration();
        } catch (NotFoundException $exception) {
            throw new InvalidConfigurationException(__($exception->getMessage()));
        }
    }
}
