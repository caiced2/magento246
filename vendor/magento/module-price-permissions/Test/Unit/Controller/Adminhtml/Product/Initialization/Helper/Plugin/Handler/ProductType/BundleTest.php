<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

// @codingStandardsIgnoreStart
namespace Magento\PricePermissions\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\ProductType;

use Magento\Bundle\Model\Option;
use Magento\Bundle\Model\Product\Type;
use Magento\Bundle\Model\ResourceModel\Selection\Collection;
use Magento\Bundle\Model\Selection;
use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\ProductType\Bundle;
use PHPUnit\Framework\MockObject\MockObject;
// @codingStandardsIgnoreEnd

use PHPUnit\Framework\TestCase;

class BundleTest extends TestCase
{
    /**
     * @var Bundle
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $productMock;

    /**
     * @var MockObject
     */
    protected $productTypeMock;

    protected function setUp(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getBundleSelectionsData'])
            ->onlyMethods(['getTypeInstance', 'getTypeId', 'setData', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productTypeMock = $this->createMock(Type::class);
        $this->productMock->expects(
            $this->any()
        )->method(
            'getTypeInstance'
        )->willReturn(
            $this->productTypeMock
        );
        // @codingStandardsIgnoreStart
        $this->model = new Bundle();
        // @codingStandardsIgnoreEnd
    }

    public function testHandleWithNonBundleProductType()
    {
        $this->productMock->expects($this->once())->method('getTypeId')->willReturn('some product type');
        $this->productMock->expects($this->never())->method('getBundleSelectionsData');
        $this->model->handle($this->productMock);
    }

    public function testHandleWithoutBundleSelectionData()
    {
        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeId'
        )->willReturn(
            \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE
        );

        $this->productMock->expects($this->once())->method('getBundleSelectionsData')->willReturn(null);

        $this->productMock->expects($this->never())->method('setData');
        $this->model->handle($this->productMock);
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHandleWithBundleOptions()
    {
        $helper = new ObjectManager($this);

        $expected = [
            [
                ['option_id' => 10],
                ['product_id' => 20],
                [
                    'product_id' => 40,
                    'option_id' => 50,
                    'delete' => true,
                    'selection_price_type' => 0,
                    'selection_price_value' => 0
                ],
                [
                    'product_id' => 60,
                    'option_id' => 70,
                    'delete' => false,
                    'selection_price_type' => 0,
                    'selection_price_value' => 0
                ],
                [
                    'product_id' => 80,
                    'option_id' => 90,
                    'delete' => false,
                    'selection_price_type' => 777,
                    'selection_price_value' => 333
                ],
            ],
        ];

        $bundleSelectionsData = [
            [
                ['option_id' => 10],
                ['product_id' => 20],
                [
                    'product_id' => 40,
                    'option_id' => 50,
                    'delete' => true,
                    'selection_price_type' => 'selection_price_type 40',
                    'selection_price_value' => 'selection_price_value 40'
                ],
                [
                    'product_id' => 60,
                    'option_id' => 70,
                    'delete' => false,
                    'selection_price_type' => 'selection_price_type 60',
                    'selection_price_value' => 'selection_price_value 60'
                ],
                [
                    'product_id' => 80,
                    'option_id' => 90,
                    'delete' => false,
                    'selection_price_type' => 'selection_price_type 80',
                    'selection_price_value' => 'selection_price_value 80'
                ],
            ],
        ];

        /** Configuring product object mock */
        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeId'
        )->willReturn(
            \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE
        );
        $this->productMock->expects(
            $this->once()
        )->method(
            'getBundleSelectionsData'
        )->willReturn(
            $bundleSelectionsData
        );
        $this->productMock->expects($this->once())->method('getStoreId')->willReturn(1);

        /** Configuring product selections collection mock */
        $selectionsMock = $helper->getCollectionMock(
            Collection::class,
            []
        );

        $optionOne = $this->getMockBuilder(Option::class)
            ->addMethods(['getSelections'])
            ->onlyMethods(['getOptionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $optionOne->expects($this->once())->method('getOptionId')->willReturn(1);
        $optionOne->expects($this->once())->method('getSelections')->willReturn(null);

        $selectionMock = $this->getMockBuilder(Selection::class)
            ->addMethods(['getProductId', 'getSelectionPriceType', 'getSelectionPriceValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $selectionMock->expects($this->once())->method('getProductId')->willReturn(80);
        $selectionMock->expects($this->once())->method('getSelectionPriceType')->willReturn(777);
        $selectionMock->expects($this->once())->method('getSelectionPriceValue')->willReturn(333);
        $selections = [$selectionMock];

        $optionTwo = $this->getMockBuilder(Option::class)
            ->addMethods(['getSelections'])
            ->onlyMethods(['getOptionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $optionTwo->expects($this->once())->method('getOptionId')->willReturn(90);
        $optionTwo->expects($this->atLeastOnce())->method('getSelections')->willReturn($selections);

        $origBundleOptions = [$optionOne, $optionTwo];

        /** Configuring product option collection mock */
        $collectionMock = $helper->getCollectionMock(\Magento\Bundle\Model\ResourceModel\Option\Collection::class, []);
        $collectionMock->expects(
            $this->once()
        )->method(
            'appendSelections'
        )->with(
            $selectionsMock
        )->willReturn(
            $origBundleOptions
        );

        /** Configuring product type object mock */
        $this->productTypeMock->expects($this->once())->method('setStoreFilter')->with(1, $this->productMock);
        $this->productTypeMock->expects(
            $this->once()
        )->method(
            'getOptionsIds'
        )->with(
            $this->productMock
        )->willReturn(
            [1, 2]
        );
        $this->productTypeMock->expects(
            $this->once()
        )->method(
            'getOptionsCollection'
        )->with(
            $this->productMock
        )->willReturn(
            $collectionMock
        );
        $this->productTypeMock->expects(
            $this->once()
        )->method(
            'getSelectionsCollection'
        )->with(
            [1, 2],
            $this->productMock
        )->willReturn(
            $selectionsMock
        );

        $this->productMock->expects($this->once())->method('setData')->with('bundle_selections_data', $expected);
        $this->model->handle($this->productMock);
    }
}
