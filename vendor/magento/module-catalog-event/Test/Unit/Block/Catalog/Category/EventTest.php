<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Block\Catalog\Category;

use Magento\Catalog\Model\Category;
use Magento\CatalogEvent\Block\Catalog\Category\Event;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    /**
     * @var Event
     */
    protected $block;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->registryMock = $this->createMock(Registry::class);

        $this->block = $objectManager->getObject(
            Event::class,
            ['registry' => $this->registryMock]
        );
    }

    protected function tearDown(): void
    {
        $this->block = null;
    }

    public function testGetIdentities()
    {
        $categoryTags = ['catalog_category_1'];
        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('getIdentities')->willReturn($categoryTags);
        $this->registryMock->expects(
            $this->once()
        )->method(
            'registry'
        )->with(
            'current_category'
        )->willReturn(
            $category
        );
        $this->assertEquals($categoryTags, $this->block->getIdentities());
    }

    public function testGetEvent()
    {
        $this->registryMock
            ->expects($this->once())
            ->method('registry')
            ->with('current_category')
            ->willReturn(new DataObject(['event' => 'some result']));

        $this->assertEquals('some result', $this->block->getEvent());
    }
}
