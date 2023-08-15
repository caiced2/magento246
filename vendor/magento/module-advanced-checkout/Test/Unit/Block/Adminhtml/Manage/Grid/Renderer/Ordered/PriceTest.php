<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Block\Adminhtml\Manage\Grid\Renderer\Ordered;

use Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer\Ordered\Price;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\CurrencyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PriceTest extends TestCase
{
    /** @var  Price */
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

        /** @var Column|MockObject $column */
        $column = $this->getMockBuilder(Column::class)
            ->disableOriginalConstructor()
            ->addMethods(['getRate', 'getCurrencyCode', 'getCurrency'])
            ->getMock();
        $column->method('getRate')
            ->willReturn(1);
        $column->method('getCurrency')->willReturn('USD');

        $localCurrency = $this->getMockBuilder(CurrencyInterface::class)
            ->getMock();
        $this->renderer = new Price($context, $localCurrency, []);
        $this->renderer->setColumn($column);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function rowDataProvider(): array
    {
        $row = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();

        $row->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->createProductMock());

        return [
            ['', new DataObject()],
            ['1.200000', $row],
        ];
    }

    /**
     * Test render with null product
     *
     * @param string $expected
     * @param \Magento\Framework\DataObject $row
     * @dataProvider rowDataProvider
     */
    public function testRenderWithNullRow($expected, $row): void
    {
        $this->assertEquals($expected, $this->renderer->render($row));
    }

    /**
     * Creates product mock
     *
     * @return Product
     */
    protected function createProductMock(): Product
    {
        /** @var Product|MockObject $productMock */
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->once())
            ->method('getPrice')
            ->willReturn(1.2);
        return $productMock;
    }
}
