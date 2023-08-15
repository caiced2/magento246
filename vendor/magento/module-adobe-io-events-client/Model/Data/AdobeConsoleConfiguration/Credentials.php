<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * Adobe Console credentials data
 *
 * @api
 * @since 1.1.0
 */
class Credentials
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $integrationType;

    /**
     * @var JWT|null
     */
    private ?JWT $jwt = null;

    /**
     * @var OAuth|null
     */
    private ?OAuth $oauth = null;

    /**
     * Return ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Return Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Return Integration Type
     *
     * @return string
     */
    public function getIntegrationType(): string
    {
        return $this->integrationType;
    }

    /**
     * Set Integration Type
     *
     * @param string $integrationType
     */
    public function setIntegrationType(string $integrationType): void
    {
        $this->integrationType = $integrationType;
    }

    /**
     * Return JWT
     *
     * @return JWT|null
     */
    public function getJwt(): ?JWT
    {
        return $this->jwt;
    }

    /**
     * Set JWT
     *
     * @param JWT $jwt
     */
    public function setJwt(JWT $jwt): void
    {
        $this->jwt = $jwt;
    }

    /**
     * Return OAuth
     *
     * @return OAuth|null
     */
    public function getOAuth(): ?OAuth
    {
        return $this->oauth;
    }

    /**
     * Set OAuth
     *
     * @param OAuth $oauth
     */
    public function setOAuth(OAuth $oauth): void
    {
        $this->oauth = $oauth;
    }
}
