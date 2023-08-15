<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Block\Checkout\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use Magento\Reward\Block\Checkout\Payment\Additional;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AdditionalTest extends TestCase
{

    /**
     * @var Additional
     */
    private $model;

    /**
     * @var MockObject
     */
    private $helperMock;

    /**
     * @var MockObject
     */
    private $storeManagerMock;

    /**
     * @var MockObject
     */
    private $rewardMock;

    /**
     * @var MockObject
     */
    private $quoteMock;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $objectManager = new ObjectManager($this);
        $this->rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['getPointsBalance'])
            ->onlyMethods(['getCurrencyAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock = $this->createMock(Session::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getBaseGrandTotal', 'getBaseRewardCurrencyAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $contextMock = $this->createMock(Context::class);
        $contextMock->expects($this->once())->method('getStoreManager')->willReturn($this->storeManagerMock);
        $data = ['reward' => $this->rewardMock];
        $this->model = $objectManager->getObject(Additional::class, [
            'context' => $contextMock,
            'checkoutSession' => $checkoutSessionMock,
            'rewardData' => $this->helperMock,
            'data' => $data
        ]);
    }

    public function testGetCanUseRewardPoints()
    {
        $websiteMock = $this->getMockForAbstractClass(WebsiteInterface::class);
        $this->helperMock->expects($this->once())->method('getHasRates')->willReturn(true);
        $this->helperMock->expects($this->once())->method('isEnabledOnFront')->willReturn(true);
        $websiteMock->expects($this->once())->method('getId')->willReturn('1');
        $this->storeManagerMock->expects($this->once())->method('getWebsite')->willReturn($websiteMock);
        $this->helperMock->expects($this->once())
            ->method('getGeneralConfig')
            ->with('min_points_balance', 1)
            ->willReturn('10');
        $this->rewardMock->expects($this->once())->method('getCurrencyAmount')->willReturn(5);
        $this->rewardMock->expects($this->once())->method('getPointsBalance')->willReturn(15);
        $this->quoteMock->expects($this->once())->method('getBaseGrandTotal')->willReturn(0);
        $this->assertFalse($this->model->getCanUseRewardPoints());
    }
}
