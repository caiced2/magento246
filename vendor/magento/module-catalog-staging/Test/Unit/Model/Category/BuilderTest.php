<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Category;

use Magento\Catalog\Model\Category;
use Magento\CatalogStaging\Model\Category\Builder;
use Magento\Staging\Model\Entity\Builder\DefaultBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    /** @var MockObject */
    protected $defaultBuilder;

    /** @var Builder */
    protected $builder;

    protected function setUp(): void
    {
        $this->defaultBuilder = $this->getMockBuilder(DefaultBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new Builder(
            $this->defaultBuilder
        );
    }

    public function testBuild()
    {
        $prototype = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['isObjectNew', 'setRowId'])
            ->getMock();

        $this->defaultBuilder->expects($this->once())
            ->method('build')
            ->with($prototype)
            ->willReturn($prototype);
        $prototype->expects($this->once())
            ->method('isObjectNew')
            ->with(true);
        $prototype->expects($this->once())
            ->method('setRowId')
            ->with(null);
        $this->assertEquals($prototype, $this->builder->build($prototype));
    }
}
