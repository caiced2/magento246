<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

/**
 * Filters event data payload.
 */
interface DataFilterInterface
{
    /**
     * Filters event data payload.
     *
     * @param string $eventCode
     * @param array $eventData
     * @return array
     * @throws EventInitializationException
     */
    public function filter(string $eventCode, array $eventData): array;
}
