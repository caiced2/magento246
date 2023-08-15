<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Quote\Model\Quote;
use Magento\Reward\Model\Plugin\TotalsCollector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TotalsCollectorTest extends TestCase
{
    /**
     * @var TotalsCollector
     */
    private $model;

    /**
     * @var MockObject
     */
    private $quoteMock;

    /**
     * @var MockObject
     */
    private $totalsCollectorMock;

    protected function setUp(): void
    {
        $this->totalsCollectorMock = $this->createMock(\Magento\Quote\Model\Quote\TotalsCollector::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['setRewardPointsBalance', 'setRewardCurrencyAmount', 'setBaseRewardCurrencyAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new TotalsCollector();
    }

    public function testBeforeCollectResetsRewardAmount()
    {
        $this->quoteMock->expects($this->once())->method('setRewardPointsBalance')->with(0);
        $this->quoteMock->expects($this->once())->method('setRewardCurrencyAmount')->with(0);
        $this->quoteMock->expects($this->once())->method('setBaseRewardCurrencyAmount')->with(0);

        $this->model->beforeCollect($this->totalsCollectorMock, $this->quoteMock);
    }
}
