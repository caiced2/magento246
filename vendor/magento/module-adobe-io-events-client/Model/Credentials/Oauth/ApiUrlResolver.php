<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Credentials\Oauth;

use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Resolves url to API endpoint
 */
class ApiUrlResolver
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Returns url to api endpoint based on environment.
     *
     * @return string
     */
    public function get(): string
    {
        $environment = $this->scopeConfig->getValue(AdobeIOConfigurationProvider::XML_ADOBE_IO_PATH_ENVIRONMENT);
        return $environment === AdobeIOConfigurationProvider::ENV_STAGING
            ? 'https://ims-na1-stg1.adobelogin.com/ims/token/v2'
            : 'https://ims-na1.adobelogin.com/ims/token/v2';
    }
}
