<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

// @codingStandardsIgnoreStart
namespace Magento\PricePermissions\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\ProductType;

use Magento\Catalog\Model\Product;
use Magento\Downloadable\Model\Link;
use Magento\Downloadable\Model\Product\Type;
use Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\ProductType\Downloadable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// @codingStandardsIgnoreEnd

class DownloadableTest extends TestCase
{
    /**
     * @var Downloadable
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $productMock;

    protected function setUp(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getDownloadableData', 'setDownloadableData'])
            ->onlyMethods(['getTypeInstance', 'getTypeId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new Downloadable();
    }

    public function testHandleWithNonDownloadableProductType()
    {
        $this->productMock->expects($this->once())->method('getTypeId')->willReturn('some product type');
        $this->productMock->expects($this->never())->method('getDownloadableData');
        $this->model->handle($this->productMock);
    }

    public function testHandleWithoutDownloadableLinks()
    {
        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeId'
        )->willReturn(
            Type::TYPE_DOWNLOADABLE
        );
        $this->productMock->expects($this->once())->method('getDownloadableData')->willReturn([]);

        $this->productMock->expects($this->never())->method('setDownloadableData');
        $this->model->handle($this->productMock);
    }

    public function testHandleWithoutDownloadableData()
    {
        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeId'
        )->willReturn(
            Type::TYPE_DOWNLOADABLE
        );
        $this->productMock->expects($this->once())->method('getDownloadableData')->willReturn(null);

        $this->productMock->expects($this->never())->method('setDownloadableData');
        $this->model->handle($this->productMock);
    }

    public function testHandleWithDownloadableData()
    {
        $linkMock = $this->createPartialMock(Link::class, ['getPrice']);
        $linkMock->expects($this->any())->method('getPrice')->willReturn(100500);
        $links = ['1' => $linkMock, '2' => $linkMock];
        $downloadableData = [
            'link' => [
                ['link_id' => 1, 'is_delete' => false],
                ['link_id' => 2, 'is_delete' => true],
                ['link_id' => 3, 'is_delete' => false],
            ],
        ];
        $expected = [
            'link' => [
                ['link_id' => 1, 'is_delete' => false, 'price' => 100500],
                ['link_id' => 2, 'is_delete' => true, 'price' => 0],
                ['link_id' => 3, 'is_delete' => false, 'price' => 0],
            ],
        ];

        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeId'
        )->willReturn(
            Type::TYPE_DOWNLOADABLE
        );
        $this->productMock->expects(
            $this->once()
        )->method(
            'getDownloadableData'
        )->willReturn(
            $downloadableData
        );

        $typeMock = $this->createMock(Type::class);
        $typeMock->expects($this->once())->method('getLinks')->willReturn($links);
        $this->productMock->expects($this->once())->method('getTypeInstance')->willReturn($typeMock);

        $this->productMock->expects($this->once())->method('setDownloadableData')->with($expected);
        $this->model->handle($this->productMock);
    }
}
