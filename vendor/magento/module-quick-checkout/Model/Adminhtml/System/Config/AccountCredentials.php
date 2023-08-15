<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Adminhtml\System\Config;

/**
 * Account credentials DTO
 */
class AccountCredentials
{
    /**
     * The configured api key
     *
     * @var string
     */
    private string $apiKey;

    /**
     * The signing secret
     *
     * @var string
     */
    private string $signingSecret;

    /**
     * The publishable key
     *
     * @var string
     */
    private string $publishableKey;

    /**
     * @param string $apiKey
     * @param string $signingSecret
     * @param string $publishableKey
     */
    public function __construct(string $apiKey, string $signingSecret, string $publishableKey)
    {
        $this->apiKey = $apiKey;
        $this->signingSecret = $signingSecret;
        $this->publishableKey = $publishableKey;
    }

    /**
     * Get api key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get signing secret
     *
     * @return string
     */
    public function getSigningSecret(): string
    {
        return $this->signingSecret;
    }

    /**
     * Get publishable key
     *
     * @return string
     */
    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }
}
