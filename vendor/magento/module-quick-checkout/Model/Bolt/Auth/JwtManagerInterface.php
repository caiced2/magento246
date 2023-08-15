<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Bolt\Auth;

interface JwtManagerInterface
{
    /**
     * Decodes a JWT and return the content
     *
     * @param string $jwt
     * @param array $jwks
     * @return array
     */
    public function decode(string $jwt, array $jwks): array;
}
