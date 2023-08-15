<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\VersionsCms\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor\HierarchyNodeStoreFilter;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection;
use PHPUnit\Framework\TestCase;

class HierarchyNodeStoreFilterTest extends TestCase
{
    /** @var  HierarchyNodeStoreFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new HierarchyNodeStoreFilter();
    }

    public function testApplyStoreFilter()
    {
        $collectionMock =
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
        $filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterMock->expects($this->once())
            ->method('getValue')
            ->willReturn('1');
        $this->filter->apply($filterMock, $collectionMock);
    }
}
