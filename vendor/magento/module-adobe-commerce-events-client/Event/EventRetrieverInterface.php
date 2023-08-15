<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for retrieving list of events.
 *
 * @api
 * @since 1.1.0
 */
interface EventRetrieverInterface
{
    /**
     * Retrieves a list of the stored events waiting to be sent.
     *
     * @return array
     * @throws LocalizedException
     * @deprecated 1.1.1 this method is replaced by a new method with a possibility to provide a limit
     * @see EventRetrieverInterface::getEventsWithLimit
     */
    public function getEvents(): array;

    /**
     * Retrieves a list of the stored events waiting to be sent.
     *
     * @param int|null $limit
     * @return array
     * @throws LocalizedException
     */
    public function getEventsWithLimit(int $limit = null): array;
}
