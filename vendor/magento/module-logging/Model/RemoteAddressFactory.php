<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Model;

use Magento\Logging\Model\RemoteAddress\RemoteAddressInterface;
use Magento\Logging\Model\RemoteAddress\RemoteAddressV4;
use Magento\Logging\Model\RemoteAddress\RemoteAddressV6;

class RemoteAddressFactory
{
    /**
     * Remote Address Factory
     *
     * @param int|string $remoteAddress
     * @return RemoteAddressInterface
     */
    public function create($remoteAddress)
    {
        if ($remoteAddress && strpos($remoteAddress, ':') !== false) {
            return new RemoteAddressV6($remoteAddress);
        }

        return new RemoteAddressV4($remoteAddress);
    }
}
