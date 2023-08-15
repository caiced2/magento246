<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\EventRetriever;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Event\EventRetrieverInterface;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class for retrieving stored event data.
 */
class WaitingEventRetriever implements EventRetrieverInterface
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var CollectionToArrayConverter
     */
    private CollectionToArrayConverter $arrayConverter;

    /**
     * @var EventRetryFilter
     */
    private EventRetryFilter $eventRetryFilter;

    /**
     * @param CollectionFactory $collectionFactory
     * @param CollectionToArrayConverter $arrayConverter
     * @param EventRetryFilter $eventRetryFilter
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        CollectionToArrayConverter $arrayConverter,
        EventRetryFilter $eventRetryFilter
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->arrayConverter = $arrayConverter;
        $this->eventRetryFilter = $eventRetryFilter;
    }

    /**
     * Returns a collection of events with waiting status.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getEvents(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', (string)EventInterface::WAITING_STATUS);

        return $this->arrayConverter->convert($collection);
    }

    /**
     * Returns a collection of events with waiting status.
     *
     * @param int|null $limit
     * @return array
     * @throws LocalizedException
     */
    public function getEventsWithLimit(int $limit = null): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', (string)EventInterface::WAITING_STATUS);
        if ($limit) {
            $collection->setPageSize($limit);
        }
        $collection = $this->eventRetryFilter->addRetryFilter($collection);
        return $this->arrayConverter->convert($collection);
    }
}
