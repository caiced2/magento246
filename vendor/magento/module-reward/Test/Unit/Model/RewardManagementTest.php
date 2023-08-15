<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\PaymentDataImporter;
use Magento\Reward\Model\RewardManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RewardManagementTest extends TestCase
{
    /**
     * @var RewardManagement
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $quoteRepositoryMock;

    /**
     * @var MockObject
     */
    protected $rewardDataMock;

    /**
     * @var MockObject
     */
    protected $importerMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->quoteRepositoryMock = $this->getMockForAbstractClass(CartRepositoryInterface::class);
        $this->rewardDataMock = $this->createMock(Data::class);
        $this->importerMock = $this->createMock(PaymentDataImporter::class);

        $this->model = $objectManager->getObject(
            RewardManagement::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'rewardData' => $this->rewardDataMock,
                'importer' => $this->importerMock
            ]
        );
    }

    public function testSetRewards()
    {
        $cartId = 100;
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->willReturn(true);

        $quoteMock = $this->createPartialMock(
            Quote::class,
            ['getPayment', 'collectTotals']
        );
        $this->quoteRepositoryMock->expects($this->once())->method('getActive')->with($cartId)->willReturn($quoteMock);
        $this->quoteRepositoryMock->expects($this->once())->method('save')->with($quoteMock);

        $paymentMock = $this->createMock(Payment::class);

        $quoteMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $quoteMock->expects($this->once())->method('collectTotals')->willReturnSelf();

        $this->importerMock->expects($this->once())
            ->method('import')
            ->with($quoteMock, $paymentMock, true)
            ->willReturnSelf();

        $this->assertTrue($this->model->set($cartId));
    }

    public function testSetRewardsIfDisabledOnFront()
    {
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->willReturn(false);
        $this->assertFalse($this->model->set(1));
    }
}
