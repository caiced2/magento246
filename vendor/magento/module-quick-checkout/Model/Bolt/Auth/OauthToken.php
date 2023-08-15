<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Bolt\Auth;

class OauthToken
{
    public const ACCESS_TOKEN_KEY = 'access_token';
    public const ACCESS_TOKEN_SCOPE_KEY = 'access_token_scope';
    public const EXPIRES_AT_KEY = 'expires_at';
    public const REFRESH_TOKEN_KEY = 'refresh_token';
    public const REFRESH_TOKEN_SCOPE_KEY = 'refresh_token_scope';
    public const ID_TOKEN_KEY = 'id_token';

    private const SCOPE_MANAGE = 'bolt.account.manage';

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $accessTokenScope;

    /**
     * @var int
     */
    private $expiresAt;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @var string
     */
    private $refreshTokenScope;

    /**
     * @var string|null
     */
    private $idToken;

    /**
     * OauthResponse class constructor
     *
     * @param string $accessToken
     * @param string $accessTokenScope
     * @param int $expiresAt
     * @param string $refreshToken
     * @param string $refreshTokenScope
     * @param string|null $idToken
     */
    public function __construct(
        string $accessToken,
        string $accessTokenScope,
        int $expiresAt,
        string $refreshToken,
        string $refreshTokenScope,
        ?string $idToken = null
    ) {
        $this->accessToken = $accessToken;
        $this->accessTokenScope = $accessTokenScope;
        $this->expiresAt = $expiresAt;
        $this->refreshToken = $refreshToken;
        $this->refreshTokenScope = $refreshTokenScope;
        $this->idToken = $idToken;
    }

    /**
     * Returns the data as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::ACCESS_TOKEN_KEY => $this->accessToken,
            self::ACCESS_TOKEN_SCOPE_KEY => $this->accessTokenScope,
            self::EXPIRES_AT_KEY => $this->expiresAt,
            self::REFRESH_TOKEN_KEY => $this->refreshToken,
            self::REFRESH_TOKEN_SCOPE_KEY => $this->refreshTokenScope,
            self::ID_TOKEN_KEY => $this->idToken,
        ];
    }

    /**
     * Get the access token
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Get the scope of the access token
     *
     * @return string
     */
    public function getAccessTokenScope(): string
    {
        return $this->accessTokenScope;
    }

    /**
     * Returns true if the token has the proper scope to manager the account details
     *
     * @return bool
     */
    public function canManageAccountDetails(): bool
    {
        return strpos($this->accessTokenScope, self::SCOPE_MANAGE) !== false;
    }

    /**
     * Returns the expiration date as a Unix timestamp
     *
     * @return int
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * Returns true if the token is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expiresAt <= strtotime("now");
    }

    /**
     * Returns the refresh token
     *
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * Returns the scope of the refresh token
     *
     * @return string
     */
    public function getRefreshTokenScope(): string
    {
        return $this->refreshTokenScope;
    }

    /**
     * Returns the id token
     *
     * @return string|null
     */
    public function getIdToken(): ?string
    {
        return $this->idToken;
    }
}
