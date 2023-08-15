<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\PaymentDataImporter;
use Magento\Reward\Observer\ProcessOrderCreationData;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessOrderCreationDataTest extends TestCase
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
     * @var ProcessOrderCreationData
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);
        $this->rewardDataMock = $this->createPartialMock(Data::class, ['isEnabledOnFront']);
        $this->importerMock = $this->createMock(PaymentDataImporter::class);

        $this->subject = $objectManager->getObject(
            ProcessOrderCreationData::class,
            ['rewardData' => $this->rewardDataMock, 'importer' => $this->importerMock]
        );
    }

    public function testPaymentDataImportIfRewardsDisabledOnFront()
    {
        $websiteId = 1;
        $observerMock = $this->createMock(Observer::class);

        $quoteMock = $this->createPartialMock(Quote::class, ['getStore']);

        $orderCreateModel = $this->createMock(Create::class);
        $orderCreateModel->expects($this->once())->method('getQuote')->willReturn($quoteMock);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrderCreateModel'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrderCreateModel')->willReturn($orderCreateModel);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(false);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportIfPaymentNotSet()
    {
        $websiteId = 1;
        $observerMock = $this->createMock(Observer::class);

        $quoteMock = $this->createPartialMock(Quote::class, ['getStore']);

        $orderCreateModel = $this->createMock(Create::class);
        $orderCreateModel->expects($this->once())->method('getQuote')->willReturn($quoteMock);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrderCreateModel', 'getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrderCreateModel')->willReturn($orderCreateModel);
        $eventMock->expects($this->once())->method('getRequest')->willReturn([]);

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(true);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportIfUseRewardsNotSet()
    {
        $websiteId = 1;
        $observerMock = $this->createMock(Observer::class);

        $quoteMock = $this->createPartialMock(Quote::class, ['getStore']);

        $orderCreateModel = $this->createMock(Create::class);
        $orderCreateModel->expects($this->once())->method('getQuote')->willReturn($quoteMock);

        $request = [
            'payment' => ['another_option' => true],
        ];

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrderCreateModel', 'getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrderCreateModel')->willReturn($orderCreateModel);
        $eventMock->expects($this->once())->method('getRequest')->willReturn($request);

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(true);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportSuccess()
    {
        $websiteId = 1;
        $observerMock = $this->createMock(Observer::class);

        $quoteMock = $this->createPartialMock(
            Quote::class,
            ['getStore', 'getPayment']
        );

        $orderCreateModel = $this->createMock(Create::class);
        $orderCreateModel->expects($this->once())->method('getQuote')->willReturn($quoteMock);

        $request = [
            'payment' => ['use_reward_points' => true],
        ];

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrderCreateModel', 'getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getOrderCreateModel')->willReturn($orderCreateModel);
        $eventMock->expects($this->once())->method('getRequest')->willReturn($request);

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->willReturn(true);

        $paymentMock = $this->createMock(Payment::class);

        $storeMock = $this->createMock(Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $quoteMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);

        $this->importerMock->expects($this->once())
            ->method('import')
            ->with($quoteMock, $paymentMock, true)->willReturnSelf();

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
