<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Credentials;

use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\AdobeIoEventsClient\Api\AccessTokenProviderInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\JWT as JwtCredentials;
use Magento\Framework\Exception\NotFoundException;

/**
 * JWT based credentials.
 */
class JWT implements CredentialsInterface
{
    /**
     * @param AccessTokenProviderInterface $accessTokenProvider
     * @param AdobeIOConfigurationProvider $configurationProvider
     */
    public function __construct(
        private AccessTokenProviderInterface $accessTokenProvider,
        private AdobeIOConfigurationProvider $configurationProvider,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getClientId(): string
    {
        return $this->getJwtCredentials()->getClientId();
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
        return $this->accessTokenProvider->getAccessToken();
    }

    /**
     * Returns JWT credentials.
     *
     * @return JwtCredentials
     * @throws InvalidConfigurationException
     */
    private function getJwtCredentials(): JwtCredentials
    {
        $credentials = $this->getConfiguration()->getFirstCredential();
        if (!$credentials->getJwt() instanceof JwtCredentials) {
            throw new InvalidConfigurationException(
                __('Jwt credentials is not found in the Adobe I/O Workspace Configuration')
            );
        }

        return $credentials->getJwt();
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
