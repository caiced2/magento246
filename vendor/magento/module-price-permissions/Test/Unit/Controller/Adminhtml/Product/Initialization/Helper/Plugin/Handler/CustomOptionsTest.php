<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PricePermissions\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\CustomOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomOptionsTest extends TestCase
{
    /**
     * @var CustomOptions
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $productMock;

    protected function setUp(): void
    {
        $this->productMock = $this->createMock(Product::class);
        $this->model = new CustomOptions();
    }

    public function testHandleProductWithoutOptions()
    {
        $this->productMock->expects(
            $this->once()
        )->method(
            'getData'
        )->with(
            'product_options'
        )->willReturn(
            null
        );

        $this->productMock->expects($this->never())->method('setData');

        $this->model->handle($this->productMock);
    }

    public function testHandleProductWithoutOriginalOptions()
    {
        $this->productMock->expects($this->once())->method('getOptions')->willReturn([]);
        $options = [
            'one' => ['price' => '10', 'price_type' => '20'],
            'two' => ['values' => 123],
            'three' => [
                'values' => [['price' => 30, 'price_type' => 40], ['price' => 50, 'price_type' => 60]],
            ],
        ];

        $expectedData = [
            'one' => ['price' => '0', 'price_type' => '0'],
            'two' => ['values' => 123],
            'three' => [
                'values' => [['price' => 0, 'price_type' => 0], ['price' => 0, 'price_type' => 0]],
            ],
        ];

        $this->productMock->expects(
            $this->once()
        )->method(
            'getData'
        )->with(
            'product_options'
        )->willReturn(
            $options
        );

        $this->productMock->expects($this->once())->method('setData')->with('product_options', $expectedData);

        $this->model->handle($this->productMock);
    }

    public function testHandleProductWithOriginalOptions()
    {
        $mockedMethodList = [
            'getOptionId',
            '__wakeup',
            'getType',
            'getPriceType',
            'getGroupByType',
            'getPrice',
            'getValues',
        ];

        $optionOne = $this->createPartialMock(Option::class, $mockedMethodList);
        $optionTwo = $this->createPartialMock(Option::class, $mockedMethodList);
        $optionTwoValue = $this->createPartialMock(
            Value::class,
            ['getOptionTypeId', 'getPriceType', 'getPrice']
        );

        $optionOne->expects($this->any())->method('getOptionId')->willReturn('one');
        $optionOne->expects($this->any())->method('getType')->willReturn(2);
        $optionOne->expects(
            $this->any()
        )->method(
            'getGroupByType'
        )->willReturn(
            ProductCustomOptionInterface::OPTION_GROUP_DATE
        );
        $optionOne->expects($this->any())->method('getPrice')->willReturn(10);
        $optionOne->expects($this->any())->method('getPriceType')->willReturn(2);

        $optionTwo->expects($this->any())->method('getOptionId')->willReturn('three');
        $optionTwo->expects($this->any())->method('getType')->willReturn(3);
        $optionTwo->expects(
            $this->any()
        )->method(
            'getGroupByType'
        )->willReturn(
            ProductCustomOptionInterface::OPTION_GROUP_SELECT
        );
        $optionTwo->expects($this->any())->method('getValues')->willReturn([$optionTwoValue]);

        $optionTwoValue->expects($this->any())->method('getOptionTypeId')->willReturn(1);
        $optionTwoValue->expects($this->any())->method('getPrice')->willReturn(100);
        $optionTwoValue->expects($this->any())->method('getPriceType')->willReturn(2);

        $this->productMock->expects(
            $this->once()
        )->method(
            'getOptions'
        )->willReturn(
            [$optionOne, $optionTwo]
        );

        $options = [
            'one' => ['price' => '10', 'price_type' => '20', 'type' => 2],
            'two' => ['values' => 123, 'type' => 10],
            'three' => [
                'type' => 3,
                'values' => [['price' => 30, 'price_type' => 40, 'option_type_id' => '1']],
            ],
        ];

        $expectedData = [
            'one' => ['price' => 10, 'price_type' => 2, 'type' => 2],
            'two' => ['values' => 123, 'type' => 10],
            'three' => [
                'type' => 3,
                'values' => [['price' => 100, 'price_type' => 2, 'option_type_id' => 1]],
            ],
        ];

        $this->productMock->expects(
            $this->once()
        )->method(
            'getData'
        )->with(
            'product_options'
        )->willReturn(
            $options
        );

        $this->productMock->expects($this->once())->method('setData')->with('product_options', $expectedData);

        $this->model->handle($this->productMock);
    }
}
