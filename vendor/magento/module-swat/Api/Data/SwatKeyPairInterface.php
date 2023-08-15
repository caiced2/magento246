<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Swat\Api\Data;

/**
 * Interface SwatKeyPairInterface
 *
 * @api
 */
interface SwatKeyPairInterface
{
    /**
     * Returns public key
     *
     * @return string
     */
    public function getPublicKey(): string;

    /**
     * Returns private key
     *
     * @return string
     */
    public function getPrivateKey(): string;

    /**
     * Returns public key in a JWKS format
     *
     * @return array|\array[][]
     */
    public function getJwks(): array;
}
