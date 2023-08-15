<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\SynchronizeEntityPeriod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SynchronizeEntityPeriodTest extends TestCase
{
    /**
     * @var SynchronizeEntityPeriod
     */
    private $model;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    protected function setUp(): void
    {
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->model = (new ObjectManager($this))->getObject(
            SynchronizeEntityPeriod::class,
            ['searchCriteriaBuilder' => $this->searchCriteriaBuilderMock]
        );
    }

    public function testExecuteThrowExceptionShouldnotBeCaught()
    {
        $this->expectException('Exception');
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilters')
            ->willThrowException(new \Exception());
        $this->model->execute();
    }
}
