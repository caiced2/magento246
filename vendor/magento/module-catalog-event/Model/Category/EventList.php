<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Model for storing and processing events for category list
 */
namespace Magento\CatalogEvent\Model\Category;

use Magento\CatalogEvent\Model\Event;
use Magento\CatalogEvent\Model\ResourceModel\Event\Collection as EventCollection;
use Magento\Framework\Registry;

/**
 * @api
 * @since 100.0.2
 */
class EventList
{
    /**
     * Store categories events
     *
     * @var array
     */
    protected $eventsToCategories = null;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $registry;

    /**
     * Event collection factory
     *
     * @var \Magento\CatalogEvent\Model\ResourceModel\Event\CollectionFactory
     */
    protected $eventCollectionFactory;

    /**
     * Event model factory
     *
     * @var \Magento\CatalogEvent\Model\ResourceModel\EventFactory
     */
    protected $eventFactory;

    /**
     * Construct
     *
     * @param Registry $registry
     * @param \Magento\CatalogEvent\Model\ResourceModel\Event\CollectionFactory $eventCollectionFactory
     * @param \Magento\CatalogEvent\Model\ResourceModel\EventFactory $eventFactory
     */
    public function __construct(
        Registry $registry,
        \Magento\CatalogEvent\Model\ResourceModel\Event\CollectionFactory $eventCollectionFactory,
        \Magento\CatalogEvent\Model\ResourceModel\EventFactory $eventFactory
    ) {
        $this->registry = $registry;
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->eventFactory = $eventFactory;
    }

    /**
     * Get event in store
     *
     * @param int $categoryId
     * @return Event|false|null
     */
    public function getEventInStore($categoryId)
    {
        if ($this->registry->registry('current_category')
            && $this->registry->registry('current_category')->getId() == $categoryId
        ) {
            // If category already loaded for page, we don't need to load categories tree
            return $this->registry->registry('current_category')->getEvent();
        }
        $eventsToCategories = $this->getEventToCategoriesList();

        if (array_key_exists($categoryId, $eventsToCategories)) {
            return $eventsToCategories[$categoryId];
        }

        return false;
    }

    /**
     * Get array with category-event association
     *
     * @return array
     */
    public function getEventToCategoriesList()
    {
        if ($this->eventsToCategories === null) {
            $this->eventsToCategories = $this->eventFactory->create()->getCategoryIdsWithEvent();

            $eventCollection = $this->getEventCollection(array_keys($this->eventsToCategories));
            foreach ($this->eventsToCategories as $catId => $eventId) {
                if ($eventId !== null) {
                    $this->eventsToCategories[$catId] = $eventCollection->getItemById($eventId);
                }
            }
        }
        return $this->eventsToCategories;
    }

    /**
     * Return event collection
     *
     * @param string[] $categoryIds
     * @return EventCollection
     */
    public function getEventCollection(array $categoryIds = null)
    {
        /** @var EventCollection $collection */
        $collection = $this->eventCollectionFactory->create();
        if ($categoryIds !== null) {
            $collection->addFieldToFilter('category_id', ['in' => $categoryIds]);
        }

        return $collection;
    }
}
