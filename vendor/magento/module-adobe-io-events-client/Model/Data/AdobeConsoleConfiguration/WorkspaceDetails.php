<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * Adobe console workspace details data
 *
 * @api
 * @since 1.1.0
 */
class WorkspaceDetails
{
    /**
     * @var Credentials[]
     */
    private array $credentials;

    /**
     * @var Runtime
     */
    private Runtime $runtime;

    /**
     * Get workspace creds
     *
     * @return Credentials[]
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * Set workspace creds
     *
     * @param Credentials[] $credentials
     */
    public function setCredentials(array $credentials): void
    {
        $this->credentials = $credentials;
    }

    /**
     * Get workspace runtime
     *
     * @return Runtime
     */
    public function getRuntime(): Runtime
    {
        return $this->runtime;
    }

    /**
     * Get workspace runtime
     *
     * @param Runtime $runtime
     */
    public function setRuntime(Runtime $runtime): void
    {
        $this->runtime = $runtime;
    }
}
