<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\Framework\DB\Select;
use Magento\GiftWrapping\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor\WrappingStatusFilter;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection;
use PHPUnit\Framework\TestCase;

class WrappingStatusFilterTest extends TestCase
{
    /** @var  WrappingStatusFilter */
    private $model;

    protected function setUp(): void
    {
        $this->model = new WrappingStatusFilter();
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
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['where'])
            ->getMock();
        $collectionMock->expects($this->once())
            ->method('getSelect')
            ->willReturn($selectMock);
        $selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status = ?', 1);
        $this->assertTrue($this->model->apply($filterMock, $collectionMock));
    }
}
