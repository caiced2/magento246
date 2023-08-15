<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Model;

use Magento\AsyncOrder\Api\AsyncPaymentInformationCustomerPublisherInterface;
use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterfaceFactory;
use Magento\AsyncOrder\Api\Data\OrderInterface;
use Magento\Checkout\Api\PaymentProcessingRateLimiterInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\App\DeploymentConfig;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Publisher of customer payment information for async order
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AsyncPaymentInformationCustomerPublisher implements AsyncPaymentInformationCustomerPublisherInterface
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
     * @var AsyncOrderMessageInterfaceFactory
     */
    private $asyncOrderFactory;

    /**
     * @var PaymentProcessingRateLimiterInterface
     */
    private $paymentRateLimiter;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param DeploymentConfig $deploymentConfig
     * @param PublisherInterface $publisher
     * @param OrderManagement $orderManagement
     * @param Quote $quote
     * @param LoggerInterface $logger
     * @param AsyncOrderMessageInterfaceFactory $asyncOrderFactory
     * @param PaymentProcessingRateLimiterInterface $paymentRateLimiter
     * @param Json $json
     */
    public function __construct(
        PaymentInformationManagementInterface $paymentInformationManagement,
        DeploymentConfig $deploymentConfig,
        PublisherInterface $publisher,
        OrderManagement $orderManagement,
        Quote $quote,
        LoggerInterface $logger,
        AsyncOrderMessageInterfaceFactory $asyncOrderFactory,
        PaymentProcessingRateLimiterInterface $paymentRateLimiter,
        Json $json
    ) {
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->deploymentConfig = $deploymentConfig;
        $this->messagePublisher = $publisher;
        $this->orderManagement = $orderManagement;
        $this->quote = $quote;
        $this->logger = $logger;
        $this->asyncOrderFactory = $asyncOrderFactory;
        $this->paymentRateLimiter = $paymentRateLimiter;
        $this->serializer = $json;
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformationAndPlaceOrder(
        string $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        if (!$this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            return $this->paymentInformationManagement
                ->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress);
        }
        if (in_array($paymentMethod->getMethod(), $this->orderManagement->getPaymentMethodsForSynchronousMode())) {
            return $this->paymentInformationManagement
                ->savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress);
        }
        $this->paymentRateLimiter->limit();
        try {
            $quote = $this->quote->load($cartId);
            if ($billingAddress !== null) {
                $customerEmail = $quote->getCustomerEmail();
                $this->orderManagement->billingAddressValidate($billingAddress, $customerEmail);
            }
            $order = $this->orderManagement->placeInitialOrder($quote);
            $this->orderManagement->processQuoteWithInitialOrder($quote, $order);
        } catch (NoSuchEntityException $e) {
            $this->logger->critical(
                'Could not get quote with quote_id ' . $cartId . ' : ' . $e->getMessage()
            );
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            $this->logger->critical(
                'Placing an order with quote_id ' . $cartId . ' is failed: ' . $e->getMessage()
            );
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('An error occurred on the server. Please try to place the order again.'),
                $e
            );
        }

        $this->publishMessage($order, $cartId, $paymentMethod, $billingAddress);

        return $order->getEntityId();
    }

    /**
     * Publish message
     *
     * @param OrderInterface $order
     * @param string $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return void
     */
    private function publishMessage(
        OrderInterface $order,
        string $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        $asyncOrder = $this->asyncOrderFactory->create();

        if ($billingAddress) {
            $asyncOrder->setAddress($billingAddress);
        }
        $asyncOrder->setCartId($cartId);
        $asyncOrder->setEmail('');
        $asyncOrder->setIsGuest(false);
        $asyncOrder->setPaymentMethod($paymentMethod);
        $asyncOrder->setOrderId($order->getEntityId());
        $asyncOrder->setIncrementId($order->getIncrementId());
        $asyncOrder->setAdditionalData(
            $this->serializer->serialize($paymentMethod->getAdditionalData())
        );

        $this->messagePublisher->publish(
            'async_order.placeOrder',
            $asyncOrder
        );
    }
}
