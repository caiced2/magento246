<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\EventRetriever;

use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event\Collection;

/**
 * Adds filters to a Collection based on the retry status of event records.
 */
class EventRetryFilter
{
    /**
     * Selects events with incremental delay based on retries_count.
     *
     * @param Collection $collection
     * @return Collection
     */
    public function addRetryFilter(Collection $collection): Collection
    {
        $collection->getSelect()->where(
            'retries_count = 0 or ' .
            'CURRENT_TIMESTAMP() > TIMESTAMPADD(MINUTE, POWER(2, retries_count - 1), updated_at)'
        );
        return $collection;
    }
}
