<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * Technical account JWT data
 *
 * @api
 * @since 1.1.0
 */
class JWT
{
    /**
     * @var string
     */
    private string $clientId;

    /**
     * @var string
     */
    private string $clientSecret;

    /**
     * @var string
     */
    private string $technicalAccountEmail;

    /**
     * @var string
     */
    private string $technicalAccountId;

    /**
     * @var array
     */
    private array $metaScopes;

    /**
     * Return Client ID
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Set Client ID
     *
     * @param string $clientId
     */
    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * Return Client Secret
     *
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * Set Client Secret
     *
     * @param string $clientSecret
     */
    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * Return Technical Account Email
     *
     * @return string
     */
    public function getTechnicalAccountEmail(): string
    {
        return $this->technicalAccountEmail;
    }

    /**
     * Set Technical Account Email
     *
     * @param string $technicalAccountEmail
     */
    public function setTechnicalAccountEmail(string $technicalAccountEmail): void
    {
        $this->technicalAccountEmail = $technicalAccountEmail;
    }

    /**
     * Return Technical Account ID
     *
     * @return string
     */
    public function getTechnicalAccountId(): string
    {
        return $this->technicalAccountId;
    }

    /**
     * Set Technical Account ID
     *
     * @param string $technicalAccountId
     */
    public function setTechnicalAccountId(string $technicalAccountId): void
    {
        $this->technicalAccountId = $technicalAccountId;
    }

    /**
     * Return Metascopes
     *
     * @return array
     */
    public function getMetaScopes(): array
    {
        return $this->metaScopes;
    }

    /**
     * Set Metascopes
     *
     * @param array $metaScopes
     */
    public function setMetaScopes(array $metaScopes): void
    {
        $this->metaScopes = $metaScopes;
    }
}
