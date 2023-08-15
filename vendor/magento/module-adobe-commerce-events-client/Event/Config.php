<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Configuration for eventing
 */
class Config
{
    private const CONFIG_PATH_ENABLED = 'adobe_io_events/eventing/enabled';
    private const CONFIG_PATH_MERCHANT_ID = 'adobe_io_events/eventing/merchant_id';
    private const CONFIG_PATH_ENVIRONMENT_ID = 'adobe_io_events/eventing/env_id';
    private const CONFIG_PATH_INSTANCE_ID = 'adobe_io_events/integration/instance_id';
    private const CONFIG_PATH_ENVIRONMENT = 'adobe_io_events/integration/adobe_io_environment';

    private const ENVIRONMENT_STAGING = 'staging';

    private const ENDPOINT_PROD = 'https://commerce-eventing.adobe.io';
    private const ENDPOINT_STAGE = 'https://commerce-eventing-stage.adobe.io';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Checks if eventing is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->config->getValue(self::CONFIG_PATH_ENABLED);
    }

    /**
     * Returns instance id.
     *
     * @return string
     */
    public function getInstanceId(): string
    {
        return (string)$this->config->getValue(self::CONFIG_PATH_INSTANCE_ID);
    }

    /**
     * Returns endpoint url.
     *
     * @return string
     */
    public function getEndpointUrl(): string
    {
        if ($this->config->getValue(self::CONFIG_PATH_ENVIRONMENT) === self::ENVIRONMENT_STAGING) {
            return self::ENDPOINT_STAGE;
        }

        return self::ENDPOINT_PROD;
    }

    /**
     * Returns Environment id.
     *
     * @return string
     */
    public function getEnvironmentId(): string
    {
        return (string)$this->config->getValue(self::CONFIG_PATH_ENVIRONMENT_ID);
    }

    /**
     * Returns Merchant id.
     *
     * @return string
     */
    public function getMerchantId(): string
    {
        return (string)$this->config->getValue(self::CONFIG_PATH_MERCHANT_ID);
    }
}
