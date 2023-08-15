<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;
use Magento\AdobeCommerceEventsClient\Event\Filter\CompositeFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see CompositeFilter class
 */
class CompositeFilterTest extends TestCase
{
    /**
     * @var CompositeFilter
     */
    private CompositeFilter $filter;

    /**
     * @var DataFilterInterface|MockObject
     */
    private $filterMockOne;

    /**
     * @var DataFilterInterface|MockObject
     */
    private $filterMockTwo;

    protected function setUp(): void
    {
        $this->filterMockOne = $this->getMockForAbstractClass(DataFilterInterface::class);
        $this->filterMockTwo = $this->getMockForAbstractClass(DataFilterInterface::class);

        $this->filter = new CompositeFilter([
            $this->filterMockOne,
            $this->filterMockTwo
        ]);
    }

    public function testFilter(): void
    {
        $eventCode = 'some.event.code';
        $eventData = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $filteredData = [
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $this->filterMockOne->expects(self::once())
            ->method('filter')
            ->with($eventCode, $eventData)
            ->willReturn($filteredData);
        $this->filterMockTwo->expects(self::once())
            ->method('filter')
            ->with($eventCode, $filteredData)
            ->willReturn(['key3' => 'value3']);

        self::assertEquals(
            ['key3' => 'value3'],
            $this->filter->filter($eventCode, $eventData)
        );
    }
}
