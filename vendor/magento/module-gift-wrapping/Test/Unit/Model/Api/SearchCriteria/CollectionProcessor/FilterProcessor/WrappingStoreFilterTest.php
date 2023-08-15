<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\GiftWrapping\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor\WrappingStoreFilter;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection;
use PHPUnit\Framework\TestCase;

class WrappingStoreFilterTest extends TestCase
{
    /** @var  WrappingStoreFilter */
    private $model;

    protected function setUp(): void
    {
        $this->model = new WrappingStoreFilter();
    }

    public function testApply()
    {
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterMock->expects($this->once())
            ->method('getValue')
            ->willReturn('1');
        $collectionMock->expects($this->once())
            ->method('addStoreAttributesToResult')
            ->with(1);
        $this->assertTrue($this->model->apply($filterMock, $collectionMock));
    }
}
