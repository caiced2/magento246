<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Model;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Api\EventRepositoryInterface;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\Event as ResourceModel;

/**
 * @inheritDoc
 */
class EventRepository implements EventRepositoryInterface
{
    /**
     * @var EventFactory
     */
    private EventFactory $eventFactory;

    /**
     * @var ResourceModel $resourceModel
     */
    private ResourceModel $resourceModel;

    /**
     * @param EventFactory $eventFactory
     * @param ResourceModel $resourceModel
     */
    public function __construct(EventFactory $eventFactory, ResourceModel $resourceModel)
    {
        $this->eventFactory = $eventFactory;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $entityId): EventInterface
    {
        $eventModel = $this->eventFactory->create();
        $this->resourceModel->load($eventModel, $entityId);

        return $eventModel;
    }

    /**
     * @inheritDoc
     */
    public function save(EventInterface $event): EventInterface
    {
        $this->resourceModel->save($event);

        return $event;
    }
}
