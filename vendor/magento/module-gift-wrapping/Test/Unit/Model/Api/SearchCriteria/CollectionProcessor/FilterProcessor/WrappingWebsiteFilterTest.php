<?php declare(strict_types=1);

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\GiftWrapping\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor\WrappingWebsitesFilter;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection;
use PHPUnit\Framework\TestCase;

class WrappingWebsiteFilterTest extends TestCase
{
    /** @var  WrappingWebsitesFilter */
    private $model;

    protected function setUp(): void
    {
        $this->model = new WrappingWebsitesFilter();
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
            ->method('applyWebsiteFilter')
            ->with('1');
        $this->assertTrue($this->model->apply($filterMock, $collectionMock));
    }
}
