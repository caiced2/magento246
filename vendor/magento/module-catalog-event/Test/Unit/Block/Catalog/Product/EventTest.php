<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Block\Catalog\Product;

use Magento\Catalog\Model\Product;
use Magento\CatalogEvent\Block\Catalog\Product\Event;
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
        $eventTags = ['catalog_category_1'];
        $productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->createMock(\Magento\CatalogEvent\Model\Event::class);
        $eventMock->expects($this->once())
            ->method('getIdentities')
            ->willReturn($eventTags);
        $productMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->registryMock->expects(
            $this->exactly(2)
        )->method(
            'registry'
        )->with(
            'current_product'
        )->willReturn(
            $productMock
        );
        $this->assertEquals($eventTags, $this->block->getIdentities());
    }

    /**
     * @param int $categoryId
     * @param array $noEventTags
     * @dataProvider getIdentitiesNoEventDataProvider
     */
    public function testGetIdentitiesNoEvent($categoryId, $noEventTags)
    {
        $productMock = $this->createPartialMock(Product::class, ['getCategoryId']);
        $productMock->expects($this->once())->method('getCategoryId')->willReturn($categoryId);

        $this->registryMock->expects(
            $this->exactly(3)
        )->method(
            'registry'
        )->with(
            'current_product'
        )->willReturn(
            $productMock
        );
        $this->assertEquals($noEventTags, $this->block->getIdentities());
    }

    public function getIdentitiesNoEventDataProvider()
    {
        return [
            [1, ['cat_c_p_1']],
            [false, []]
        ];
    }
}
