<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Model\RemoteAddress;

class RemoteAddressV6 implements RemoteAddressInterface
{
    /**
     * @var string
     */
    private string $remoteAddress;

    /**
     * RemoteAddressV6 constructor.
     * @param string $remoteAddress
     */
    public function __construct(string $remoteAddress)
    {
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * Long Representation of a v6 address
     *
     * @return int
     */
    public function getLongFormat(): int
    {
        return 0;
    }

    /**
     * Text Representation of a v6 Remote Address
     *
     * @return string
     */
    public function getTextFormat(): string
    {
        return (string)$this->remoteAddress;
    }
}
