<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\CatalogEvent\Model\Event;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    /**
     * @var Event
     */
    protected $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(Event::class);
    }

    protected function tearDown(): void
    {
        $this->model = null;
    }

    public function testGetIdentities()
    {
        $categoryId = 'categoryId';
        $eventId = 'eventId';
        $this->model->setCategoryId($categoryId);
        $this->model->setId($eventId);
        $eventTags = [
            Event::CACHE_TAG . '_' . $eventId,
            Category::CACHE_TAG . '_' . $categoryId,
            Product::CACHE_PRODUCT_CATEGORY_TAG . '_' . $categoryId,
        ];
        $this->assertEquals($eventTags, $this->model->getIdentities());
    }
}
