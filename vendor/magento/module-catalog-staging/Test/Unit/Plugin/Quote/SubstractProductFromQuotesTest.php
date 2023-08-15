<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Plugin\Quote;

use Magento\Catalog\Model\Product;
use Magento\CatalogStaging\Plugin\Quote\SubstractProductFromQuotes;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubstractProductFromQuotesTest extends TestCase
{
    /**
     * @var SubstractProductFromQuotes
     */
    private $model;

    /**
     * @var MockObject
     */
    private $versionManagerMock;

    protected function setup(): void
    {
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->model = new SubstractProductFromQuotes($this->versionManagerMock);
    }

    public function testAroundSubtractProductFromQuotesWhenVersionIsPreview()
    {
        $quoteResourceMock = $this->createMock(Quote::class);
        $productMock = $this->createMock(Product::class);
        $closureResult = 'closure_result';

        $closure = function ($productMock) use ($closureResult) {
            return $closureResult;
        };

        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);

        $this->assertEquals(
            $quoteResourceMock,
            $this->model->aroundSubtractProductFromQuotes($quoteResourceMock, $closure, $productMock)
        );
    }

    public function testAroundSubtractProductFromQuotesWhenVersionIsNotPreview()
    {
        $quoteResourceMock = $this->createMock(Quote::class);
        $productMock = $this->createMock(Product::class);
        $closureResult = 'closure_result';

        $closure = function ($productMock) use ($closureResult) {
            return $closureResult;
        };

        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(false);

        $this->assertEquals(
            $closureResult,
            $this->model->aroundSubtractProductFromQuotes($quoteResourceMock, $closure, $productMock)
        );
    }
}
