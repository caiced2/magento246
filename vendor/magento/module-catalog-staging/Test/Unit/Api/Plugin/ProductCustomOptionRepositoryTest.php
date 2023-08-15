<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Api\Plugin;

use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\CatalogStaging\Api\Plugin\ProductCustomOptionRepository;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductCustomOptionRepositoryTest extends TestCase
{
    /**
     * @var ProductCustomOptionRepository
     */
    private $plugin;

    /**
     * @var MockObject
     */
    private $productRepositoryMock;

    /**
     * @var MockObject
     */
    private $customOptionRepositoryMock;

    /**
     * @var MockObject
     */
    private $optionMock;

    /**
     * @var MockObject
     */
    private $productMock;

    /**
     * @var MockObject
     */
    private $versionManagerMock;

    protected function setUp(): void
    {
        $this->customOptionRepositoryMock =
            $this->getMockBuilder(ProductCustomOptionRepositoryInterface::class)
                ->getMockForAbstractClass();
        $this->optionMock = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = new ProductCustomOptionRepository(
            $this->productRepositoryMock,
            $this->versionManagerMock
        );
    }

    public function testBeforeSaveForNewUpdateWithCustomOption()
    {
        $productSku = 'product_sku';
        $optionId = 1;
        $this->optionMock->expects($this->atLeastOnce())->method('getProductSku')->willReturn($productSku);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->productRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->exactly(2))->method('getOptionId')->willReturn($optionId);
        $this->productMock->expects($this->once())->method('getOptionById')->willReturn(null);
        $this->optionMock->expects($this->once())->method('setOptionId')->with(null);
        $this->optionMock->expects($this->never())->method('setData');
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }

    public function testBeforeSaveForExistingProductWithCustomOption()
    {
        $productSku = 'product_sku';
        $optionId = 1;
        $this->optionMock->expects($this->atLeastOnce())->method('getProductSku')->willReturn($productSku);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->productRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->exactly(2))->method('getOptionId')->willReturn($optionId);
        $this->productMock->expects($this->once())->method('getOptionById')->willReturn(2);
        $this->optionMock->expects($this->never())->method('setOptionId');
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }

    public function testBeforeSaveForNewOption()
    {
        $productSku = 'product_sku';
        $this->optionMock->expects($this->atLeastOnce())->method('getProductSku')->willReturn($productSku);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->productRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->once())->method('getOptionId')->willReturn(null);
        $this->optionMock->expects($this->never())->method('setOptionId');
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }

    public function testBeforeSaveForExistingProductWithCustomOptionAndValues()
    {
        $productSku = 'product_sku';
        $optionId = 1;
        $value = [
            'option_type_id' => 8,
            'price' => 10
        ];
        $newValue = [
            'option_type_id' => null,
            'price' => 10
        ];
        $this->optionMock->expects($this->atLeastOnce())->method('getProductSku')->willReturn($productSku);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->productRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->exactly(2))->method('getOptionId')->willReturn($optionId);
        $this->productMock->expects($this->once())->method('getOptionById')->willReturn(null);
        $this->optionMock->expects($this->once())->method('setOptionId')->with(null);
        $this->optionMock->expects($this->exactly(3))
            ->method('getData')
            ->with('values', null)
            ->willReturn([$value]);
        $this->optionMock->expects($this->once())->method('setData')->with('values', [$newValue]);
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }

    public function testBeforeSaveForExistingProductWithCustomOptionAndGetValuesEmpty()
    {
        $productSku = 'product_sku';
        $optionId = 1;
        $value = [
            'option_type_id' => 8,
            'price' => 10
        ];
        $newValue = [
            'option_type_id' => null,
            'price' => 10
        ];
        $this->optionMock->expects($this->atLeastOnce())->method('getProductSku')->willReturn($productSku);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->productRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->exactly(2))->method('getOptionId')->willReturn($optionId);
        $this->productMock->expects($this->once())->method('getOptionById')->willReturn(null);
        $this->optionMock->expects($this->once())->method('setOptionId')->with(null);
        $this->optionMock->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn(null);

        $productCustomOptionValuesMock =
            $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                ->setMethods(['getData'])
                ->getMockForAbstractClass();
        $productCustomOptionValuesMock->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn($value);

        $this->optionMock->expects($this->atLeastOnce())
            ->method('getValues')
            ->willReturn([$productCustomOptionValuesMock]);
        $this->optionMock->expects($this->once())->method('setData')->with('values', [$newValue]);
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }

    public function testBeforeSaveWithoutProductSku()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->expectExceptionMessage('The ProductSku is empty. Set the ProductSku and try again.');
        $this->optionMock->expects($this->atLeastOnce())->method('getProductSku')->willReturn(null);
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }

    public function testBeforeSaveForExistingProductWithoutPreview()
    {
        $productSku = 'product_sku';
        $optionId = 1;
        $this->optionMock->expects($this->once())->method('getProductSku')->willReturn($productSku);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(false);
        $this->productRepositoryMock
            ->expects($this->never())
            ->method('get')
            ->with($productSku)
            ->willReturn($this->productMock);
        $this->optionMock->expects($this->exactly(0))->method('getOptionId')->willReturn($optionId);
        $this->productMock->expects($this->never())->method('getOptionById')->willReturn(2);
        $this->optionMock->expects($this->never())->method('setOptionId');
        $this->plugin->beforeSave($this->customOptionRepositoryMock, $this->optionMock);
    }
}
