<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Model\Plugin;

use Magento\CustomerBalance\Model\Plugin\CollectQuoteTotalsPlugin;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectQuoteTotalsPluginTest extends TestCase
{
    /**
     * @var \Magento\Reward\Model\Plugin\TotalsCollector
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
            ->addMethods(['setBaseCustomerBalAmountUsed', 'setCustomerBalanceAmountUsed'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new CollectQuoteTotalsPlugin();
    }

    public function testBeforeCollectResetsRewardAmount()
    {
        $this->quoteMock->expects($this->once())->method('setBaseCustomerBalAmountUsed')->with(0);
        $this->quoteMock->expects($this->once())->method('setCustomerBalanceAmountUsed')->with(0);
        $this->model->beforeCollectQuoteTotals($this->totalsCollectorMock, $this->quoteMock);
    }
}
