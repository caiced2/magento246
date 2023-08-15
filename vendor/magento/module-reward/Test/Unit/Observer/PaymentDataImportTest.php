<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\PaymentDataImporter;
use Magento\Reward\Observer\PaymentDataImport;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentDataImportTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rewardDataMock;

    /**
     * @var MockObject
     */
    protected $importerMock;

    /**
     * @var PaymentDataImport
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->rewardDataMock = $this->createMock(Data::class);
        $this->importerMock = $this->createMock(PaymentDataImporter::class);

        $this->subject = $objectManager->getObject(
            PaymentDataImport::class,
            ['rewardData' => $this->rewardDataMock, 'importer' => $this->importerMock]
        );
    }

    public function testPaymentDataImportIfRewardsDisabledOnFront()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->willReturn(false);
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportSuccess()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->willReturn(true);

        $inputMock =
            $this->getMockBuilder(DataObject::class)
                ->addMethods(['getAdditionalData'])
                ->disableOriginalConstructor()
                ->getMock();
        $inputMock->expects($this->once())
            ->method('getAdditionalData')
            ->willReturn(['use_reward_points' => true]);
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getIsMultiShipping'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(true);
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->addMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getRule', 'getInput', 'getPayment'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInput')->willReturn($inputMock);
        $eventMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->importerMock->expects($this->once())
            ->method('import')
            ->with($quoteMock, $inputMock, true)->willReturnSelf();

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportOnePageCheckout()
    {
        $observerMock = $this->createMock(Observer::class);
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->willReturn(true);

        $inputMock =
            $this->getMockBuilder(DataObject::class)
                ->addMethods(['getAdditionalData', 'getUseRewardPoints'])
                ->disableOriginalConstructor()
                ->getMock();
        $inputMock->expects($this->never())->method('getUseRewardPoints');
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getIsMultiShipping'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(false);
        $paymentMock = $this->getMockBuilder(Payment::class)
            ->addMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getRule', 'getInput', 'getPayment'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getInput')->willReturn($inputMock);
        $eventMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->importerMock->expects($this->never())->method('import');

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
