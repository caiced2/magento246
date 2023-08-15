<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swat\Api\Data;

/**
 * Interface JwtInterface
 *
 * @api
 */
interface JwtInterface
{
    /**
     * Loads passed token, returning false if not in proper format
     *
     * @param string $jwt
     * @return bool
     */
    public function loadToken(string $jwt): bool;

    /**
     * Returns payload of JWT
     *
     * @return array
     */
    public function getPayload(): array;

    /**
     * Returns JWT as a string
     *
     * @return string
     */
    public function getTokenString(): string;

    /**
     * Determine if the JWT is still active and not expired.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Determine if the JWT signature is valid.
     *
     * @return bool
     */
    public function isSignatureValid(): bool;
}
