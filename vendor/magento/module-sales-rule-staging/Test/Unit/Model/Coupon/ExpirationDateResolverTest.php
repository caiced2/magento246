<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model\Coupon;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Api\Data\CouponSearchResultInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRuleStaging\Model\Coupon\ExpirationDateResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExpirationDateResolverTest extends TestCase
{
    /**
     * @var CouponRepositoryInterface|MockObject
     */
    private $couponRepositoryMock;

    /**
     * Filter Builder
     *
     * @var FilterBuilder|MockObject
     */
    private $filterBuilderMock;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder|MockObject
     */
    private $criteriaBuilderMock;

    /**
     * @var RuleRepositoryInterface|MockObject
     */
    private $ruleRepositoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ExpirationDateResolver
     */
    private $model;

    /**
     * @var MockObject
     */
    private $observerMock;

    protected function setUp(): void
    {
        $this->couponRepositoryMock = $this->getMockForAbstractClass(CouponRepositoryInterface::class);
        $methods = ['setField', 'setValue', 'setConditionType', 'create'];
        $this->filterBuilderMock = $this->createPartialMock(FilterBuilder::class, $methods);
        $this->criteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->ruleRepositoryMock = $this->getMockForAbstractClass(RuleRepositoryInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->observerMock = $this->createMock(Observer::class);
        $this->model = new ExpirationDateResolver(
            $this->couponRepositoryMock,
            $this->filterBuilderMock,
            $this->criteriaBuilderMock,
            $this->ruleRepositoryMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $couponMock = $this->prepareCouponMock();
        $this->couponRepositoryMock->expects($this->once())->method('save')->with($couponMock);
        $this->model->execute($this->observerMock);
    }

    public function testExecuteIfExceptionWasThrown()
    {
        $couponId = 2;
        $exception = new \Exception('MessageText');
        $message = __(
            'An error occurred during processing; coupon with id %1 expiration date'
            . ' wasn\'t updated. The error message was: %2',
            $couponId,
            $exception->getMessage()
        );
        $couponMock = $this->prepareCouponMock();
        $exception = new \Exception('MessageText');
        $this->couponRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($couponMock)
            ->willThrowException($exception);
        $couponMock->expects($this->once())->method('getCouponId')->willReturn($couponId);
        $this->loggerMock->expects($this->once())->method('error')->with($message);
        $this->model->execute($this->observerMock);
    }

    /**
     * Prepare the coupon mock for test
     *
     * @return MockObject
     */
    private function prepareCouponMock()
    {
        $ruleId = 1;
        $filterMock = $this->createMock(Filter::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $searchResult = $this->getMockForAbstractClass(CouponSearchResultInterface::class);
        $couponMock = $this->getMockForAbstractClass(CouponInterface::class);
        $ruleMock = $this->getMockForAbstractClass(RuleInterface::class);
        $this->observerMock->expects($this->once())->method('getData')->with('entity_ids')->willReturn([$ruleId]);
        $this->filterBuilderMock->expects($this->once())->method('setField')->willReturnSelf();
        $this->filterBuilderMock->expects($this->once())->method('setValue')->with([$ruleId])->willReturnSelf();
        $this->filterBuilderMock->expects($this->once())->method('setConditionType')->willReturnSelf();
        $this->filterBuilderMock->expects($this->once())->method('create')->willReturn($filterMock);
        $this->criteriaBuilderMock->expects($this->once())->method('addFilters')->with([$filterMock]);
        $this->criteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->couponRepositoryMock
            ->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResult);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$couponMock]);
        $couponMock->expects($this->once())->method('getRuleId')->willReturn($ruleId);
        $this->ruleRepositoryMock->expects($this->once())->method('getById')->with($ruleId)->willReturn($ruleMock);
        $ruleMock->expects($this->once())->method('getToDate')->willReturn('2016-09-20');
        $couponMock->expects($this->once())->method('setExpirationDate')->with('2016-09-20');
        return $couponMock;
    }
}
