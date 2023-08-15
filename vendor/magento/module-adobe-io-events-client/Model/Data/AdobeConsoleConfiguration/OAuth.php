<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * Oauth data
 *
 * @api
 */
class OAuth
{
    /**
     * @var string
     */
    private string $clientId;

    /**
     * @var array
     */
    private array $clientSecrets;

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
    private array $scopes;

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
        return reset($this->clientSecrets);
    }

    /**
     * Return Client Secrets
     *
     * @return array
     */
    public function getClientSecrets(): array
    {
        return $this->clientSecrets;
    }

    /**
     * Set Client Secrets
     *
     * @param array $clientSecrets
     */
    public function setClientSecrets(array $clientSecrets): void
    {
        $this->clientSecrets = $clientSecrets;
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
     * Return scopes
     *
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Set scopes
     *
     * @param array $scopes
     */
    public function setScopes(array $scopes): void
    {
        $this->scopes = $scopes;
    }
}
