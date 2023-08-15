<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Collector;

/**
 * Interface for collecting events
 */
interface CollectorInterface
{
    public const IGNORED_MODULES = 'AdobeCommerceEvents';

    /**
     * Collects events for Adobe Commerce module
     *
     * @param string $modulePath
     * @return EventData[]
     */
    public function collect(string $modulePath): array;
}
