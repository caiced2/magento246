<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrderGraphQl\Test\Unit\Model;

use Magento\AsyncOrder\Api\AsyncPaymentInformationCustomerPublisherInterface as CustomerPublisher;
use Magento\AsyncOrder\Api\AsyncPaymentInformationGuestPublisherInterface as GuestPublisher;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\SubmitQuoteValidator;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Payment;
use Magento\Quote\Model\Quote\Address;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\AsyncOrderGraphQl\Model\Cart\PlaceOrderAsync;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrderAsyncTest extends TestCase
{
    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentManagement;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var SubmitQuoteValidator
     */
    private $submitQuoteValidator;

    /**
     * @var CustomerPublisher
     */
    private $customerPublisher;

    /**
     * @var GuestPublisher
     */
    private $guestPublisher;

    /**
     * @var PlaceOrderAsync
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->paymentManagement = $this->getMockForAbstractClass(PaymentMethodManagementInterface::class);
        $this->cartManagement = $this->getMockForAbstractClass(CartManagementInterface::class);

        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->submitQuoteValidator = $this->createMock(SubmitQuoteValidator::class);
        $this->customerPublisher = $this->createMock(CustomerPublisher::class);
        $this->guestPublisher = $this->createMock(GuestPublisher::class);

        $this->model = $objectManager->getObject(
            PlaceOrderAsync::class,
            [
                'paymentManagement' => $this->paymentManagement,
                'cartManagement' => $this->cartManagement,
                'deploymentConfig' => $this->deploymentConfig,
                'submitQuoteValidator' => $this->submitQuoteValidator,
                'customerPublisher' => $this->customerPublisher,
                'guestPublisher' => $this->guestPublisher
            ]
        );
    }

    public function testExecuteAsyncDisabled(): void
    {
        $cart = $this->createMock(Quote::class);
        $maskedCartId = 'maskedCartId';
        $userId = 1;
        $paymentMethod = null;
        $orderId = 111;
        $cartId = 999;
        $cart->expects($this->once())->method('getId')->willReturn($cartId);

        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            'checkout/async'
        )->willReturn(false);

        $this->cartManagement->expects(
            $this->once()
        )->method('placeOrder')->with(
            $cartId,
            $paymentMethod
        )->willReturn($orderId);

        $this->assertEquals(
            $orderId,
            $this->model->execute($cart, $maskedCartId, $userId, $paymentMethod)
        );
    }

    public function testExecuteAsyncWithoutPaymentMethod(): void
    {
        $cart = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getPayment', 'getBillingAddress', 'getCustomerEmail'])
            ->getMock();
        $billingAddress = $this->createMock(Address::class);
        $maskedCartId = 'maskedCartId';
        $paymentMethod = null;
        $userId = 1;
        $cartId = 999;
        $cart->expects($this->once())->method('getId')->willReturn($cartId);
        $cart->expects($this->once())->method('getBillingAddress')->willReturn($billingAddress);

        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            'checkout/async'
        )->willReturn(true);

        $this->paymentManagement->expects(
            $this->once()
        )->method('get')->with(
            $cartId
        )->willReturn(null);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage((string)__('Enter a valid payment method and try again.'));

        $this->model->execute($cart, $maskedCartId, $userId, $paymentMethod);
    }

    /**
     * @param int $userId
     * @dataProvider executeDataProvider
     */
    public function testExecuteAsyncEnabled(int $userId): void
    {
        $cart = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getPayment', 'getBillingAddress', 'getCustomerEmail'])
            ->getMock();
        $billingAddress = $this->createMock(Address::class);
        $paymentInterface = $this->getMockForAbstractClass(
            PaymentInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['setChecks', 'getData']
        );
        $paymentInterface->expects($this->once())->method('getData')->willReturn([]);
        $payment = $this->createMock(Payment::class);
        $maskedCartId = 'maskedCartId';
        $paymentMethod = null;
        $orderId = 111;
        $cartId = 999;
        $cart->expects($this->once())->method('getId')->willReturn($cartId);
        $cart->expects($this->once())->method('getPayment')->willReturn($payment);
        $cart->expects($this->once())->method('getBillingAddress')->willReturn($billingAddress);

        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            'checkout/async'
        )->willReturn(true);

        $this->paymentManagement->expects(
            $this->once()
        )->method('get')->with(
            $cartId
        )->willReturn($paymentInterface);

        if ($userId === 0) {
            $guestEmail = 'guestEmail';
            $cart->expects($this->once())->method('getCustomerEmail')->willReturn($guestEmail);
            $this->guestPublisher->expects(
                $this->once()
            )->method('savePaymentInformationAndPlaceOrder')->with(
                $maskedCartId,
                $guestEmail,
                $paymentInterface,
                $billingAddress
            )->willReturn($orderId);
        } else {
            $this->customerPublisher->expects(
                $this->once()
            )->method('savePaymentInformationAndPlaceOrder')->with(
                $cartId,
                $paymentInterface,
                $billingAddress
            )->willReturn($orderId);
        }

        $this->assertEquals(
            $orderId,
            $this->model->execute($cart, $maskedCartId, $userId, $paymentMethod)
        );
    }

    public function executeDataProvider(): array
    {
        return [
            [
                'userId' => 0
            ],
            [
                'userId' => 1
            ]
        ];
    }
}
