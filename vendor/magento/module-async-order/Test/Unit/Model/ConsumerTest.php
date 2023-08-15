<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Test\Unit\Model;

use Magento\AsyncOrder\Model\Consumer;
use Magento\AsyncOrder\Model\GuestOrderProcessor;
use Magento\AsyncOrder\Model\CustomerOrderProcessor;
use Magento\AsyncOrder\Model\OrderRejecter;
use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConsumerTest extends TestCase
{
    /**
     * @var NotifierInterface
     */
    private $notifier;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GuestOrderProcessor
     */
    private $guestOrderProcessor;

    /**
     * @var CustomerOrderProcessor
     */
    private $registeredCustomerOrderProcessor;

    /**
     * @var OrderRejecter
     */
    private $orderRejecter;

    /**
     * @var Consumer
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->notifier = $this->getMockForAbstractClass(NotifierInterface::class);
        $this->guestOrderProcessor = $this->createMock(GuestOrderProcessor::class);
        $this->registeredCustomerOrderProcessor = $this->createMock(CustomerOrderProcessor::class);
        $this->orderRejecter = $this->createMock(OrderRejecter::class);

        $this->model = $objectManager->getObject(
            Consumer::class,
            [
                'logger' => $this->logger,
                'notifier' => $this->notifier,
                'guestOrderProcessor' => $this->guestOrderProcessor,
                'registeredCustomerOrderProcessor' => $this->registeredCustomerOrderProcessor,
                'orderRejecter' => $this->orderRejecter
            ]
        );
    }

    /**
     * @param bool $ifGuest
     * @param bool $ifError
     * @dataProvider processDataProvider
     */
    public function testProcess(bool $ifGuest, bool $ifError): void
    {
        $asyncOrderMessage = $this->getMockForAbstractClass(AsyncOrderMessageInterface::class);

        $asyncOrderMessage->expects(
            $this->once()
        )->method('getIsGuest')->willReturn($ifGuest);

        $errorMsg = __('Error');

        if ($ifGuest) {
            if ($ifError) {
                $this->guestOrderProcessor->expects(
                    $this->once()
                )->method('process')->with(
                    $asyncOrderMessage
                )->willThrowException(new LocalizedException($errorMsg));
            } else {
                $this->guestOrderProcessor->expects(
                    $this->once()
                )->method('process')->with(
                    $asyncOrderMessage
                )->willReturnSelf();
            }
        } else {
            if ($ifError) {
                $this->registeredCustomerOrderProcessor->expects(
                    $this->once()
                )->method('process')->with(
                    $asyncOrderMessage
                )->willThrowException(new LocalizedException($errorMsg));
            } else {
                $this->registeredCustomerOrderProcessor->expects(
                    $this->once()
                )->method('process')->with(
                    $asyncOrderMessage
                )->willReturnSelf();
            }
        }

        if ($ifError) {
            $incrementId = '00000001';

            $asyncOrderMessage->expects(
                $this->once()
            )->method('getIncrementId')->willReturn($incrementId);

            $rejectComment = $errorMsg . ' order ID - ' . $incrementId;

            $this->notifier->expects(
                $this->once()
            )->method('addCritical')->willReturnSelf();

            $this->logger->expects(
                $this->once()
            )->method('critical')->willReturnSelf();

            $this->orderRejecter->expects(
                $this->once()
            )->method('reject')->with($asyncOrderMessage, $rejectComment)->willReturnSelf();
        }
        $this->model->process($asyncOrderMessage);
    }
    
    public function processDataProvider(): array
    {
        return [
            [
                'ifGuest' => true,
                'ifError' => true
            ],
            [
                'ifGuest' => true,
                'ifError' => false
            ],
            [
                'ifGuest' => false,
                'ifError' => true
            ],
            [
                'ifGuest' => false,
                'ifError' => false
            ]
        ];
    }
}
