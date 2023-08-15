<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Link\Converter;
use Magento\Catalog\Model\Product\Link\Resolver;
use Magento\Catalog\Model\ProductLink\Repository;
use Magento\CatalogStaging\Model\Product\Builder as BuilderModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $resolverMock;

    /**
     * @var MockObject
     */
    protected $linkConverterMock;

    /**
     * @var MockObject
     */
    protected $linkRepositoryMock;

    /**
     * @var BuilderModel
     */
    protected $builder;

    protected function setUp(): void
    {
        $this->resolverMock = $this->getMockBuilder(Resolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->linkConverterMock = $this->getMockBuilder(Converter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->linkRepositoryMock = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new BuilderModel(
            $this->resolverMock,
            $this->linkConverterMock,
            $this->linkRepositoryMock
        );
    }

    public function testBuild()
    {
        $groupedLinkData = [30, 50, 70];
        $prototypeMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->linkConverterMock->expects($this->once())
            ->method('convertLinksToGroupedArray')
            ->with($prototypeMock)
            ->willReturn($groupedLinkData);
        $this->linkRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($prototypeMock);
        $this->resolverMock->expects($this->once())
            ->method('override')
            ->with($groupedLinkData);
        $result = $this->builder->build($prototypeMock);
        $this->assertInstanceOf(get_class($prototypeMock), $result);
    }
}
