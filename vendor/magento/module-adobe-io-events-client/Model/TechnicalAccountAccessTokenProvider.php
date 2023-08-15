<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model;

use Magento\AdobeImsApi\Api\Data\TokenResponseInterface;
use Magento\AdobeImsApi\Api\Data\TokenResponseInterfaceFactory;
use Magento\AdobeIoEventsClient\Api\AccessTokenProviderInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\ImsJwtApi\JwtClient;
use Magento\Framework\Exception\AuthorizationException;

/**
 * Get the access token from the technical account JWT
 */
class TechnicalAccountAccessTokenProvider implements AccessTokenProviderInterface
{
    /**
     * @var TokenResponseInterfaceFactory
     */
    private TokenResponseInterfaceFactory $tokenResponseFactory;

    /**
     * @var TokenResponseInterface|null
     */
    private ?TokenResponseInterface $lastToken = null;

    /**
     * @var JwtClient
     */
    private JwtClient $jwtClient;

    /**
     * @param TokenResponseInterfaceFactory $tokenResponseFactory
     * @param JwtClient $jwtClient
     */
    public function __construct(
        TokenResponseInterfaceFactory $tokenResponseFactory,
        JwtClient $jwtClient
    ) {
        $this->tokenResponseFactory = $tokenResponseFactory;
        $this->jwtClient = $jwtClient;
    }

    /**
     * Call IMS to fetch Access Token from Technical Account JWT
     *
     * @return TokenResponseInterface
     * @throws AuthorizationException
     * @throws InvalidConfigurationException
     */
    public function getAccessToken(): TokenResponseInterface
    {
        if ($this->lastToken != null) {
            return $this->lastToken;
        }

        $response = $this->jwtClient->fetchJwtTokenResponse();

        if (!is_array($response) || empty($response['access_token'])) {
            throw new AuthorizationException(__('Could not login to Adobe IMS.'));
        }

        $this->lastToken = $this->tokenResponseFactory->create(['data' => $response]);

        return $this->lastToken;
    }
}
