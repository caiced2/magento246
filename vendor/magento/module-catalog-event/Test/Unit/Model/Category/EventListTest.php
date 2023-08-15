<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Model\Category;

use Magento\Catalog\Model\Category;
use Magento\CatalogEvent\Model\Category\EventList;
use Magento\CatalogEvent\Model\ResourceModel\Event;
use Magento\CatalogEvent\Model\ResourceModel\Event\Collection;
use Magento\CatalogEvent\Model\ResourceModel\Event\CollectionFactory;
use Magento\CatalogEvent\Model\ResourceModel\EventFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventListTest extends TestCase
{
    /** @var EventList */
    protected $eventList;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var Registry|MockObject */
    protected $registry;

    /** @var MockObject */
    protected $collectionFactory;

    /** @var Collection|MockObject */
    protected $eventCollection;

    /** @var EventFactory|MockObject */
    protected $eventFactory;

    /** @var Event|MockObject */
    protected $resourceEvent;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->eventFactory = $this->createPartialMock(
            EventFactory::class,
            ['create']
        );

        $this->eventCollection = $this->createMock(Collection::class);
        $this->collectionFactory->expects($this->any())->method('create')->willReturn(
            $this->eventCollection
        );
        $this->resourceEvent = $this->createMock(Event::class);
        $this->eventFactory->expects($this->any())->method('create')->willReturn(
            $this->resourceEvent
        );

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->eventList = $this->objectManagerHelper->getObject(
            EventList::class,
            [
                'registry' => $this->registry,
                'eventCollectionFactory' => $this->collectionFactory,
                'eventFactory' => $this->eventFactory
            ]
        );
    }

    public function testGetEventInStoreFromCurrentCategory()
    {
        $categoryId = 1;
        /** @var \Magento\CatalogEvent\Model\Event $event */
        $event = $this->createMock(\Magento\CatalogEvent\Model\Event::class);
        /** @var Category|MockObject $category */
        $category = $this->objectManagerHelper->getObject(
            Category::class,
            [
                'data' => ['id' => $categoryId, 'event' => $event]
            ]
        );
        $this->registry->expects($this->any())->method('registry')->with('current_category')->willReturn(
            $category
        );
        $returnEvent = $this->eventList->getEventInStore($categoryId);
        $this->assertEquals($event, $returnEvent);
    }

    /**
     * Data provider for getting list of categories from store
     *
     * @return array
     */
    public function getEventInStoreDataProvider()
    {
        return [
            [
                [2 => 3, 3 => null, 4 => null],
                2,
                3,
            ],
            [
                [2 => 3, 3 => null, 4 => null],
                4,
                null
            ],
            [
                [2 => 3, 3 => null, 4 => null],
                5,
                false
            ]
        ];
    }

    /**
     * @param array $categoryList
     * @param int $categoryId
     * @param mixed $expectedResult
     *
     * @dataProvider getEventInStoreDataProvider
     */
    public function testGetEventInStore($categoryList, $categoryId, $expectedResult)
    {
        $this->resourceEvent->expects($this->once())->method('getCategoryIdsWithEvent')->willReturn(
            $categoryList
        );
        $eventCollectionReturnMap = [];
        foreach ($categoryList as $eventId) {
            if ($eventId) {
                $eventCollectionReturnMap[] = [$eventId, $eventId];
            }
        }
        $this->eventCollection->expects($this->any())->method('getItemById')->willReturnMap(
            $eventCollectionReturnMap
        );
        $returnEvent = $this->eventList->getEventInStore($categoryId);
        $this->assertEquals($expectedResult, $returnEvent);
    }

    /**
     * Data provider for category-event association array
     *
     * @return array
     */
    public function getCategoryListDataProvider()
    {
        return [
            [
                [2 => 3, 3 => null, 4 => null],
                1,
            ],
            [
                [4 => 3, 3 => 1, 5 => 4],
                3
            ],
            [
                [],
                0
            ],
            [
                [2 => null, 3 => null, 4 => null, 10 => null],
                0
            ],
        ];
    }

    /**
     * @param array $categoryList
     * @param int $getItemCallNumber
     *
     * @dataProvider getCategoryListDataProvider
     */
    public function testGetEventToCategoriesList($categoryList, $getItemCallNumber)
    {
        $this->resourceEvent->expects($this->once())->method('getCategoryIdsWithEvent')->willReturn(
            $categoryList
        );

        $event = new DataObject();
        $this->eventCollection->expects($this->exactly($getItemCallNumber))->method('getItemById')->willReturn(
            $event
        );
        $eventsToCategory = $this->eventList->getEventToCategoriesList();
        $this->assertIsArray($eventsToCategory);
        foreach ($categoryList as $key => $value) {
            if ($value !== null) {
                $this->assertInstanceOf(DataObject::class, $eventsToCategory[$key]);
            } else {
                $this->assertNull($eventsToCategory[$key]);
            }
        }
    }

    public function testGetEventCollectionWithIds()
    {
        $this->eventCollection->expects($this->once())->method('addFieldToFilter');
        $collection = $this->eventList->getEventCollection([1, 3]);
        $this->assertInstanceOf(Collection::class, $collection);
    }

    public function testGetEventCollectionWithoutIds()
    {
        $this->eventCollection->expects($this->never())->method('addFieldToFilter');
        $collection = $this->eventList->getEventCollection();
        $this->assertInstanceOf(Collection::class, $collection);
    }
}
