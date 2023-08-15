<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Block\Adminhtml\Widget\Grid;

use ArrayIterator;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\LayoutInterface;
use Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser\Tile;
use Magento\VisualMerchandiser\Model\Category\Products;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests category products tile block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TileTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Tile
     */
    private $tileBlock;

    /**
     * @var Collection|MockObject
     */
    private $collection;

    /**
     * @var Products|MockObject
     */
    private $products;

    /**
     * @var DataObject
     */
    private $parentBlock;

    /**
     * Set up instances and mock objects
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $request = $this->createMock(Http::class);
        $request->expects($this->atLeastOnce())->method('getParam')->willReturn('');
        $request->expects($this->any())->method('has')->willReturn(false);

        $context = $this->createMock(Context::class);
        $context->expects($this->any())->method('getRequest')->willReturn($request);

        $session = $this->createMock(Session::class);
        $context->expects($this->any())->method('getBackendSession')->willReturn($session);

        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $select
            ->expects($this->any())
            ->method('group')
            ->willReturnSelf();
        $collection = $this->createMock(Collection::class);
        $collection
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([]));
        $collection
            ->expects($this->any())
            ->method('getSelect')
            ->willReturn($select);
        $this->collection = $collection;

        $this->products = $this->createMock(Products::class);
        $this->products
            ->expects($this->atLeastOnce())
            ->method('getCollectionForGrid')
            ->willReturn($this->collection);

        $category = $this->createMock(Category::class);
        $category
            ->expects($this->any())
            ->method('getProductsPosition')
            ->willReturn(['a' => 'b']);

        $coreRegistry = $this->createMock(Registry::class);
        $catalogImage = $this->createMock(Image::class);
        $backendHelper = $this->createMock(Data::class);

        $this->tileBlock = $this->objectManager->getObject(
            Tile::class,
            [
                'context' => $context,
                'backendHelper' => $backendHelper,
                'coreRegistry' => $coreRegistry,
                'catalogImage' => $catalogImage,
                'products' => $this->products
            ]
        );

        /** @var LayoutInterface|MockObject $layout */
        $layout = $this->getMockForAbstractClass(LayoutInterface::class);
        $this->parentBlock = new DataObject();

        $layout
            ->expects($this->any())
            ->method('getParentName')
            ->willReturn('parent');
        $layout
            ->expects($this->any())
            ->method('getBlock')
            ->willReturnMap([['parent', $this->parentBlock]]);

        $this->tileBlock->setLayout($layout);
    }

    /**
     * Tests if collection is returned and set from _prepareCollection
     *
     * @dataProvider prepareCollectionDataProvider
     */
    public function testPrepareCollection($titleBlockCacheKey, $parentBlockCacheKey, $expectedProductsCacheKey)
    {
        $this->tileBlock->setPositionCacheKey($titleBlockCacheKey);
        $this->parentBlock->setPositionCacheKey($parentBlockCacheKey);

        $this->products
            ->expects($this->any())
            ->method('setCacheKey')
            ->with($expectedProductsCacheKey);

        $this->tileBlock->setData('id', 1);
        $collection = $this->tileBlock->getPreparedCollection();
        $this->assertEquals($this->collection, $this->tileBlock->getCollection());
        $this->assertEquals($this->collection, $collection);
    }

    /**
     * Provides variants for cache key value in tile block
     *
     * @return array
     */
    public function prepareCollectionDataProvider()
    {
        return [
            ['x', 'y', 'x'],
            ['x', null, 'x'],
            [null, null, null],
        ];
    }
}
