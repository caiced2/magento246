<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Block\Adminhtml\Manage\Grid\Renderer;

use Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer\Product;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Wishlist\Model\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /** @var  Product */
    private $renderer;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->atLeastOnce())
            ->method('getEscaper')
            ->willReturn(new Escaper());

        /** @var Grid|MockObject $grid */
        $grid = $this->getMockBuilder(Grid::class)
            ->disableOriginalConstructor()
            ->setMethods(['getListType'])
            ->getMock();

        $grid->expects($this->atLeastOnce())
            ->method('getListType')
            ->willReturn('testListType');
        /** @var Column|MockObject $column */
        $column = $this->getMockBuilder(Column::class)
            ->addMethods(['getIndex'])
            ->onlyMethods(['getGrid'])
            ->disableOriginalConstructor()
            ->getMock();

        $column->expects($this->atLeastOnce())
            ->method('getGrid')
            ->willReturn($grid);
        $column->expects($this->atLeastOnce())
            ->method('getIndex')
            ->willReturn('index_name');
        $this->renderer = new Product($context);
        $this->renderer->setColumn($column);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function rowDataProvider(): array
    {
        $rowAsProduct = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var MockObject $rowAsProductConfigure */
        $rowAsProductConfigure = $this->createProductMock();
        $rowAsProductConfigure->expects($this->once())
            ->method('getid')
            ->willReturn('id');

        /** @var MockObject $rowAsWishlistItem */
        $rowAsWishlistItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rowAsWishlistItem->expects($this->once())
            ->method('getId')
            ->willReturn('id');
        $rowAsWishlistItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->createProductMock());

        /** @var MockObject $rowAsWishlistItem */
        $rowAsOrderItem = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rowAsOrderItem->expects($this->once())
            ->method('getId')
            ->willReturn('id');
        $rowAsOrderItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->createProductMock());

        return [
            [
                '<a href="javascript:void(0)" disabled="disabled" class="action-configure disabled">Configure</a>',
                new DataObject()
            ],
            [
                '<a href="javascript:void(0)" disabled="disabled" class="action-configure disabled">Configure</a>',
                $rowAsProduct
            ],
            [
                '<a href="javascript:void(0)" list_type = "testListType" item_id = id '
                . 'class="action-configure ">Configure</a>',
                $rowAsProductConfigure
            ],
            [
                '<a href="javascript:void(0)" list_type = "testListType" item_id = id '
                . 'class="action-configure ">Configure</a>',
                $rowAsWishlistItem
            ],
            [
                '<a href="javascript:void(0)" list_type = "testListType" item_id = id '
                . 'class="action-configure ">Configure</a>',
                $rowAsOrderItem
            ],
        ];
    }

    /**
     * Test render with null product
     *
     * @param string $expected
     * @param \Magento\Framework\DataObject $row
     * @throws LocalizedException
     * @dataProvider rowDataProvider
     */
    public function testRenderWithNullRow($expected, $row): void
    {
        $this->assertEquals($expected, $this->renderer->render($row));
    }

    /**
     * Creates product mock
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function createProductMock(): \Magento\Catalog\Model\Product
    {
        /** @var \Magento\Catalog\Model\Product | MockObject $productMock */
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->once())
            ->method('canConfigure')
            ->willReturn(true);
        return $productMock;
    }
}
