<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Generator;

use Magento\AdobeCommerceEventsGenerator\Generator\Collector\CollectorException;

/**
 * Interface for collector instances
 */
interface CollectorInterface
{
    /**
     * Collects based on event code
     *
     * @param string $eventCode
     * @return array
     * @throws CollectorException
     */
    public function collect(string $eventCode): array;
}
