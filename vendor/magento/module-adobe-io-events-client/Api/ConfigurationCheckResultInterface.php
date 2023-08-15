<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Api;

/**
 * Contains configuration status for each necessary config setting
 *
 * @api
 * @since 1.1.0
 */
interface ConfigurationCheckResultInterface
{
    public const STATUS = 'status';
    public const TECHNICAL_SERVICE_ACCOUNT_CONFIGURED = 'technical_service_account_configured';
    public const TECHNICAL_SERVICE_ACCOUNT_CAN_CONNECT = 'technical_service_account_can_connect';
    public const PROVIDER_ID_CONFIGURED = 'provider_id_configured';
    public const PROVIDER_ID_VALID = 'provider_id_valid';

    /**
     * Overall ok/error status of the configuration
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Whether the technical service account is configured
     *
     * @return bool
     */
    public function getTechnicalServiceAccountConfigured(): bool;

    /**
     * Whether providers are configured to allow the service account to connect
     *
     * @return bool
     */
    public function getTechnicalServiceAccountCanConnectToIoEvents(): bool;

    /**
     * Get the provider id if configured
     *
     * @return string
     */
    public function getProviderIdConfigured(): string;

    /**
     * Whether a configured provider id is valid
     *
     * @return bool
     */
    public function getProviderIdValid(): bool;
}
