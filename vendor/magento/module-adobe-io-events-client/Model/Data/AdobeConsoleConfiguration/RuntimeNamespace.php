<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * Adobe console workspace runtime namespace data
 *
 * @api
 * @since 1.1.0
 */
class RuntimeNamespace
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $auth;

    /**
     * Get namespace name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set namespace name
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get runtime auth
     *
     * @return string
     */
    public function getAuth(): string
    {
        return $this->auth;
    }

    /**
     * Set runtime auth
     *
     * @param string $auth
     */
    public function setAuth(string $auth): void
    {
        $this->auth = $auth;
    }
}
