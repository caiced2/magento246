<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Model\RemoteAddress;

class RemoteAddressV4 implements RemoteAddressInterface
{
    /**
     * @var string|int
     */
    private $remoteAddress;

    /**
     * RemoteAddressV4 constructor.
     * @param string|int $remoteAddress
     */
    public function __construct($remoteAddress)
    {
        $this->remoteAddress = $remoteAddress;
        if (is_numeric($this->remoteAddress)) {
            $this->remoteAddress = long2ip($this->remoteAddress);
        }
    }

    /**
     * Return IP long format
     *
     * @return int
     */
    public function getLongFormat(): int
    {
        return  $this->remoteAddress ? (int) ip2long($this->remoteAddress) : 0;
    }

    /**
     * Text Representation of Remote Address
     *
     * @return string
     */
    public function getTextFormat(): string
    {
        return (string) $this->remoteAddress;
    }
}
