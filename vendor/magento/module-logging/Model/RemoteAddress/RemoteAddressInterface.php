<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Model\RemoteAddress;

interface RemoteAddressInterface
{
    /**
     * Return IP long format
     *
     * @return int
     */
    public function getLongFormat(): int;

    /**
     * IP Text Format representation
     *
     * @return string
     */
    public function getTextFormat(): string;
}
