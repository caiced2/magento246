<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Test\Unit\Model;

use Magento\AsyncOrder\Model\GuestOrderProcessor;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterface;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GuestOrderProcessorTest extends TestCase
{
    /**
     * @var GuestPaymentInformationManagementInterface
     */
    private $guestPaymentInformationManagement;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var GuestOrderProcessor
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->guestPaymentInformationManagement = $this->getMockForAbstractClass(
            GuestPaymentInformationManagementInterface::class
        );

        $this->serializer = $this->createMock(
            Json::class
        );

        $this->model = $objectManager->getObject(
            GuestOrderProcessor::class,
            [
                'guestPaymentInformationManagement' => $this->guestPaymentInformationManagement,
                'serializer' => $this->serializer
            ]
        );
    }

    public function testProcess(): void
    {
        $additionalData = 'Additional Data';
        $data = ['Additional Data'];

        $paymentMethod = $this->getMockForAbstractClass(
            PaymentInterface::class
        );

        $address = $this->getMockForAbstractClass(
            AddressInterface::class
        );

        $asyncOrderMessage = $this->getMockForAbstractClass(
            AsyncOrderMessageInterface::class
        );

        $asyncOrderMessage->expects(
            $this->once()
        )->method('getCartId')->willReturn('cart_id');

        $asyncOrderMessage->expects(
            $this->once()
        )->method('getEmail')->willReturn('email');

        $asyncOrderMessage->expects(
            $this->atLeastOnce()
        )->method('getPaymentMethod')->willReturn($paymentMethod);

        $asyncOrderMessage->expects(
            $this->once()
        )->method('getAddress')->willReturn($address);

        $asyncOrderMessage->expects(
            $this->once()
        )->method('getAdditionalData')->willReturn($additionalData);

        $this->serializer->expects(
            $this->once()
        )->method('unserialize')->with($additionalData)->willReturn($data);

        $paymentMethod->expects(
            $this->once()
        )->method('setAdditionalData')->with($data)->willReturnSelf();

        $this->guestPaymentInformationManagement->expects(
            $this->once()
        )->method('savePaymentInformationAndPlaceOrder')->willReturn(123);

        $this->model->process($asyncOrderMessage);
    }
}
