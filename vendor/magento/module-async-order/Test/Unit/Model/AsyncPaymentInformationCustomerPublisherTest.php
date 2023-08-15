<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Test\Unit\Model;

use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterfaceFactory;
use Magento\Checkout\Api\PaymentProcessingRateLimiterInterface;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use PHPUnit\Framework\TestCase;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\AsyncOrder\Model\AsyncPaymentInformationCustomerPublisher;
use Magento\AsyncOrder\Model\Quote;
use Magento\AsyncOrder\Model\Order;
use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AsyncPaymentInformationCustomerPublisherTest extends TestCase
{
    /**
     * @var PaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var PublisherInterface
     */
    private $messagePublisher;

    /**
     * @var OrderManagement
     */
    private $orderManagement;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var IdentityGeneratorInterface
     */
    private $asyncOrderFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var PaymentProcessingRateLimiterInterface
     */
    private $paymentRateLimiter;

    /**
     * @var AsyncPaymentInformationCustomerPublisher
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->paymentInformationManagement = $this->getMockForAbstractClass(
            PaymentInformationManagementInterface::class
        );
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->messagePublisher = $this->getMockForAbstractClass(PublisherInterface::class);
        $this->orderManagement = $this->createMock(OrderManagement::class);
        $this->quote = $this->createMock(Quote::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->asyncOrderFactory = $this->getMockBuilder(AsyncOrderMessageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->serializer = $this->createMock(Json::class);
        $this->paymentRateLimiter = $this->getMockForAbstractClass(PaymentProcessingRateLimiterInterface::class);

        $this->model = $objectManager->getObject(
            AsyncPaymentInformationCustomerPublisher::class,
            [
                'paymentInformationManagement' => $this->paymentInformationManagement,
                'deploymentConfig' => $this->deploymentConfig,
                'messagePublisher' => $this->messagePublisher,
                'orderManagement' => $this->orderManagement,
                'quote' => $this->quote,
                'logger' => $this->logger,
                'asyncOrderFactory' => $this->asyncOrderFactory,
                'paymentRateLimiter' => $this->paymentRateLimiter,
                'serializer' => $this->serializer
            ]
        );
    }

    public function testPublishAsyncDisabled(): void
    {
        $orderId = 999;
        $cartId = '101';
        $paymentMethod = $this->getMockForAbstractClass(PaymentInterface::class);
        $billingAddress = $this->getMockForAbstractClass(AddressInterface::class);

        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            'checkout/async'
        )->willReturn(false);

        $this->orderManagement->expects(
            $this->never()
        )->method('getPaymentMethodsForSynchronousMode');

        $this->paymentInformationManagement->expects(
            $this->once()
        )->method('savePaymentInformationAndPlaceOrder')->with(
            $cartId,
            $paymentMethod,
            $billingAddress
        )->willReturn($orderId);

        $this->assertEquals(
            $orderId,
            $this->model->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress)
        );
    }

    public function testPublishAsyncForPaymentMethodsForSynchronousMode(): void
    {
        $orderId = 999;
        $cartId = '101';
        $paymentMethodsForSynchronousMode = [
            'some payment method 1',
            'some payment method 2'
        ];
        $paymentMethodType = 'some payment method 1';

        $paymentMethod = $this->getMockForAbstractClass(PaymentInterface::class);
        $billingAddress = $this->getMockForAbstractClass(AddressInterface::class);

        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            'checkout/async'
        )->willReturn(true);

        $paymentMethod->expects(
            $this->once()
        )->method('getMethod')->willReturn($paymentMethodType);

        $this->orderManagement->expects(
            $this->once()
        )->method('getPaymentMethodsForSynchronousMode')->willReturn($paymentMethodsForSynchronousMode);

        $this->paymentInformationManagement->expects(
            $this->once()
        )->method('savePaymentInformationAndPlaceOrder')->with(
            $cartId,
            $paymentMethod,
            $billingAddress
        )->willReturn($orderId);

        $this->assertEquals(
            $orderId,
            $this->model->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress)
        );
    }

    public function testPublishAsyncEnabled(): void
    {
        $orderId = 999;
        $cartId = '101';

        $additionalData = 'Additional Data';
        $data = ['Additional Data'];

        $paymentMethodsForSynchronousMode = [
            'some payment method 1',
            'some payment method 2'
        ];
        $paymentMethodType = 'checkmo';

        $paymentMethod = $this->getMockForAbstractClass(PaymentInterface::class);
        $billingAddress = $this->getMockForAbstractClass(AddressInterface::class);
        $quote = $this->createMock(Quote::class);
        $order = $this->createMock(Order::class);
        $order->expects($this->atLeastOnce())->method('getEntityId')->willReturn($orderId);
        $operation = $this->getMockForAbstractClass(AsyncOrderMessageInterface::class);

        $this->deploymentConfig->expects(
            $this->once()
        )->method('get')->with(
            'checkout/async'
        )->willReturn(true);

        $paymentMethod->expects(
            $this->once()
        )->method('getMethod')->willReturn($paymentMethodType);

        $this->orderManagement->expects(
            $this->once()
        )->method('getPaymentMethodsForSynchronousMode')->willReturn($paymentMethodsForSynchronousMode);

        $this->paymentInformationManagement->expects(
            $this->never()
        )->method('savePaymentInformationAndPlaceOrder');

        $this->paymentRateLimiter->expects(
            $this->once()
        )->method('limit')->willReturnSelf();

        $this->quote->expects(
            $this->once()
        )->method('load')->with(
            $cartId
        )->willReturn($quote);

        $this->orderManagement->expects(
            $this->once()
        )->method('placeInitialOrder')->with(
            $quote
        )->willReturn($order);

        $this->orderManagement->expects(
            $this->once()
        )->method('processQuoteWithInitialOrder')->with($quote, $order)->willReturnSelf();

        $this->asyncOrderFactory->expects(
            $this->once()
        )->method('create')->willReturn($operation);

        $paymentMethod->expects(
            $this->once()
        )->method('getAdditionalData')->willReturn($data);

        $this->serializer->expects(
            $this->once()
        )->method('serialize')->with($data)->willReturn($additionalData);

        $operation->expects(
            $this->once()
        )->method('setAdditionalData')->with($additionalData)->willReturnSelf();

        $this->messagePublisher->expects(
            $this->once()
        )->method('publish')->with('async_order.placeOrder', $operation)->willReturnSelf();

        $this->assertEquals(
            $orderId,
            $this->model->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress)
        );
    }
}
