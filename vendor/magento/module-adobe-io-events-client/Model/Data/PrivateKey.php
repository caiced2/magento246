<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data;

/**
 * Private key definition
 *
 * @api
 * @since 1.1.0
 */
class PrivateKey
{
    private const BEGIN_PRIVATE_KEY = "-----BEGIN PRIVATE KEY-----";
    private const END_PRIVATE_KEY = "-----END PRIVATE KEY-----";

    /**
     * @var string
     */
    private string $data;

    /**
     * Return Data
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Set Data
     *
     * @param string $data
     */
    public function setData(string $data): void
    {
        $key = str_replace(self::BEGIN_PRIVATE_KEY, "", $data);
        $key = str_replace(self::END_PRIVATE_KEY, "", $key);

        // Obscure type configuration will replace new lines by spaces, we need new lines for private keys
        $key = str_replace(" ", "\r\n", $key);

        $this->data = self::BEGIN_PRIVATE_KEY . $key . self::END_PRIVATE_KEY;
    }
}
