<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Bolt\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

class JwtManager implements JwtManagerInterface
{
    /**
     * Decodes a jwt using
     *
     * @param string $jwt
     * @param array $jwks
     * @return array
     */
    public function decode(string $jwt, array $jwks): array
    {
        // @phpstan-ignore-next-line
        return (array)JWT::decode($jwt, JWK::parseKeySet($jwks));
    }
}
