<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Test\Unit\Model\Reporting;

use Exception;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\DataCollectorInterface;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\Filters;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\ReportData;
use Magento\QuickCheckoutAdminPanel\Model\Reporting\ReportingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ReportingServiceTest extends TestCase
{
    /**
     * @var DataCollectorInterface|MockObject
     */
    private $collector;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ReportingService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collector = $this->createMock(DataCollectorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testZeroCollectors(): void
    {
        $this->givenTheCollectors([]);
        $filters = $this->prepareFilters();
        $this->assertEmpty($this->service->generate($filters));
    }

    public function testSuccess(): void
    {
        $this->givenTheCollectors([$this->collector]);
        $filters = $this->prepareFilters();
        $this->collector->expects($this->once())
            ->method('collect')
            ->with($filters)
            ->willReturn(new ReportData('test', ['foo' => 'bar']));
        $expectedResult = ['test' => ['foo' => 'bar']];
        $this->assertEquals($expectedResult, $this->service->generate($filters));
    }

    public function testDuplicatedData(): void
    {
        $this->givenTheCollectors([$this->collector, $this->collector]);
        $filters = $this->prepareFilters();
        $this->collector->expects($this->exactly(2))
            ->method('collect')
            ->with($filters)
            ->willReturn(new ReportData('test', ['foo' => 'bar']));
        $expectedResult = ['test' => ['foo' => 'bar']];
        $this->assertEquals($expectedResult, $this->service->generate($filters));
    }

    public function testCollectorFailure(): void
    {
        $this->givenTheCollectors([$this->collector]);
        $filters = $this->prepareFilters();
        $this->collector->expects($this->once())
            ->method('collect')
            ->with($filters)
            ->willThrowException(new Exception('Unexpected error'));
        $this->logger->expects($this->once())->method('error');
        $this->assertEmpty($this->service->generate($filters));
    }

    /**
     * @param array $collectors
     * @return void
     */
    private function givenTheCollectors(array $collectors): void
    {
        $this->service = new ReportingService($collectors, $this->logger);
    }

    /**
     * @return Filters
     */
    private function prepareFilters(): Filters
    {
        return new Filters(
            '2022-08-07',
            '2022-10-14',
            1,
            'all'
        );
    }
}
