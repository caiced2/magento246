<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedProductStaging\Test\Unit\Model\Product\Operation\Update;

use Magento\Catalog\Model\Product;
use Magento\CatalogStaging\Model\Product\Operation\Update\TemporaryUpdateProcessor;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\GroupedProductStaging\Model\Product\Operation\Update\FlushAssociatedProductCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlushAssociatedProductCacheTest extends TestCase
{
    /**
     * @var FlushAssociatedProductCache
     */
    private $model;

    /**
     * @var MockObject
     */
    private $objectMock;

    /**
     * @var MockObject
     */
    private $temporaryUpdateMock;

    /**
     * @var MockObject
     */
    private $typeInstanceMock;

    protected function setUp(): void
    {
        $this->typeInstanceMock = $this->createMock(Grouped::class);
        $this->objectMock = $this->createMock(Product::class);
        $this->temporaryUpdateMock = $this->createMock(
            TemporaryUpdateProcessor::class
        );
        $this->model = new FlushAssociatedProductCache();
    }

    public function testBeforeLoadEntity()
    {
        $this->objectMock->expects($this->once())->method('getTypeId')->willReturn(Grouped::TYPE_CODE);
        $this->objectMock->expects($this->once())->method('getTypeInstance')->willReturn($this->typeInstanceMock);
        $this->typeInstanceMock
            ->expects($this->once())
            ->method('flushAssociatedProductsCache')
            ->with($this->objectMock);
        $this->model->beforeLoadEntity($this->temporaryUpdateMock, $this->objectMock);
    }

    public function testBeforeBuildEntity()
    {
        $this->objectMock->expects($this->once())->method('getTypeId')->willReturn('code');
        $this->objectMock->expects($this->never())->method('getTypeInstance');
        $this->model->beforeBuildEntity($this->temporaryUpdateMock, $this->objectMock);
    }
}
