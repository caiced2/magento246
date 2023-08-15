<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Quote\Api\Data\TotalsExtensionInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Quote;
use Magento\Reward\Model\Plugin\CartTotalRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartTotalRepositoryTest extends TestCase
{
    /**
     * @var CartTotalRepository
     */
    protected $model;

    /**
     * @var Quote|MockObject
     */
    protected $quote;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected $quoteRepository;

    /**
     * @var TotalsInterface|MockObject
     */
    protected $totals;

    /**
     * @var TotalsExtensionFactory|MockObject
     */
    protected $totalsExtensionFactory;

    /**
     * @var TotalsExtensionInterface|MockObject
     */
    protected $totalsExtension;

    /**
     * @var \Magento\Quote\Model\Cart\CartTotalRepository|MockObject
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getRewardPointsBalance',
                'getRewardCurrencyAmount',
                'getBaseRewardCurrencyAmount',
            ])
            ->getMock();

        $this->quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->totals = $this->getMockBuilder(TotalsInterface::class)
            ->getMock();

        $this->totalsExtension = $this->getMockBuilder(TotalsExtensionInterface::class)
            ->setMethods(['setRewardPointsBalance', 'setRewardCurrencyAmount', 'setBaseRewardCurrencyAmount'])
            ->getMockForAbstractClass();

        $this->totalsExtensionFactory = $this->getMockBuilder(TotalsExtensionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->totalsExtensionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->totalsExtension);

        $this->subject = $this->getMockBuilder(\Magento\Quote\Model\Cart\CartTotalRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new CartTotalRepository(
            $this->quoteRepository,
            $this->totalsExtensionFactory
        );
    }

    public function testAfterGet()
    {
        $cartId = 1;
        $rewardPointsBalance = 1.;
        $rewardCurrencyAmount = 1.;
        $baseRewardCurrencyAmount = 1.;

        $this->quote->expects($this->atLeastOnce())
            ->method('getRewardPointsBalance')
            ->willReturn($rewardPointsBalance);
        $this->quote->expects($this->once())
            ->method('getRewardCurrencyAmount')
            ->willReturn($rewardCurrencyAmount);
        $this->quote->expects($this->once())
            ->method('getBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);

        $this->quoteRepository->expects($this->once())
            ->method('getActive')
            ->willReturn($this->quote);

        $this->totalsExtension->expects($this->once())
            ->method('setRewardPointsBalance')
            ->willReturn($rewardPointsBalance);
        $this->totalsExtension->expects($this->once())
            ->method('setRewardCurrencyAmount')
            ->willReturn($rewardCurrencyAmount);
        $this->totalsExtension->expects($this->once())
            ->method('setBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);

        $this->totals->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->totalsExtension);
        $this->totals->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->totalsExtension)
            ->willReturnSelf();

        $result = $this->model->afterGet($this->subject, $this->totals, $cartId);
        $this->assertEquals($this->totalsExtension, $result->getExtensionAttributes());
    }

    public function testAfterGetCreateExtensionAttributes()
    {
        $cartId = 1;
        $rewardPointsBalance = 1.;
        $rewardCurrencyAmount = 1.;
        $baseRewardCurrencyAmount = 1.;

        $this->quote->expects($this->atLeastOnce())
            ->method('getRewardPointsBalance')
            ->willReturn($rewardPointsBalance);
        $this->quote->expects($this->once())
            ->method('getRewardCurrencyAmount')
            ->willReturn($rewardCurrencyAmount);
        $this->quote->expects($this->once())
            ->method('getBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);

        $this->quoteRepository->expects($this->once())
            ->method('getActive')
            ->willReturn($this->quote);

        $this->totalsExtension->expects($this->once())
            ->method('setRewardPointsBalance')
            ->willReturn($rewardPointsBalance);
        $this->totalsExtension->expects($this->once())
            ->method('setRewardCurrencyAmount')
            ->willReturn($rewardCurrencyAmount);
        $this->totalsExtension->expects($this->once())
            ->method('setBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);

        $this->totals->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->totals->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->totalsExtension)
            ->willReturnSelf();

        $result = $this->model->afterGet($this->subject, $this->totals, $cartId);
        $this->assertNull($result->getExtensionAttributes());
    }
}
