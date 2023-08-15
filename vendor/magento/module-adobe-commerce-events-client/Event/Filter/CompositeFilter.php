<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;

/**
 * Works with a list of filters as a single filter.
 */
class CompositeFilter implements DataFilterInterface
{
    /**
     * @var DataFilterInterface[]
     */
    private array $filters;

    /**
     * @param DataFilterInterface[] $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @inheritDoc
     */
    public function filter(string $eventCode, array $eventData): array
    {
        foreach ($this->filters as $eventDataFilter) {
            $eventData = $eventDataFilter->filter($eventCode, $eventData);
        }

        return $eventData;
    }
}
