<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Credentials;

use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Config\Source\AuthorizationType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NotFoundException;

/**
 * Creates credentials object based on configured auth type.
 */
class ScopeConfigCredentialsFactory
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CredentialsFactory $credentialsFactory
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private CredentialsFactory $credentialsFactory
    ) {
    }

    /**
     * Creates credentials object based on configured auth type.
     *
     * If Authorization Type value is not saved in the configuration uses JWT as default for backward compatibility.
     *
     * @return CredentialsInterface
     * @throws NotFoundException
     */
    public function create(): CredentialsInterface
    {
        $authType = $this->scopeConfig->getValue(AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_AUTHORIZATION_TYPE)
            ?? AuthorizationType::JWT;

        return $this->credentialsFactory->create($authType);
    }
}
