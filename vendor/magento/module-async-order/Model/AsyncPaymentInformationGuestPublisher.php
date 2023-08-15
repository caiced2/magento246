<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Model;

use Magento\AsyncOrder\Api\AsyncPaymentInformationGuestPublisherInterface;
use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterfaceFactory;
use Magento\AsyncOrder\Api\Data\OrderInterface;
use Magento\Checkout\Api\PaymentProcessingRateLimiterInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Publisher of guest payment information for async order
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AsyncPaymentInformationGuestPublisher implements AsyncPaymentInformationGuestPublisherInterface
{
    /**
     * @var GuestPaymentInformationManagementInterface
     */
    private $guestPaymentInformationManagement;

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
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

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
     * @param GuestPaymentInformationManagementInterface $guestPaymentInformationManagement
     * @param DeploymentConfig $deploymentConfig
     * @param PublisherInterface $publisher
     * @param OrderManagement $orderManagement
     * @param Quote $quote
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param LoggerInterface $logger
     * @param AsyncOrderMessageInterfaceFactory $asyncOrderFactory
     * @param PaymentProcessingRateLimiterInterface $paymentRateLimiter
     * @param Json $json
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        GuestPaymentInformationManagementInterface $guestPaymentInformationManagement,
        DeploymentConfig $deploymentConfig,
        PublisherInterface $publisher,
        OrderManagement $orderManagement,
        Quote $quote,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        LoggerInterface $logger,
        AsyncOrderMessageInterfaceFactory $asyncOrderFactory,
        PaymentProcessingRateLimiterInterface $paymentRateLimiter,
        Json $json
    ) {
        $this->guestPaymentInformationManagement = $guestPaymentInformationManagement;
        $this->deploymentConfig = $deploymentConfig;
        $this->messagePublisher = $publisher;
        $this->orderManagement = $orderManagement;
        $this->quote = $quote;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->logger = $logger;
        $this->asyncOrderFactory = $asyncOrderFactory;
        $this->paymentRateLimiter = $paymentRateLimiter;
        $this->serializer = $json;
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        if (!$this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            return $this->guestPaymentInformationManagement
                ->savePaymentInformationAndPlaceOrder($cartId, $email, $paymentMethod, $billingAddress);
        }
        if (in_array($paymentMethod->getMethod(), $this->orderManagement->getPaymentMethodsForSynchronousMode())) {
            return $this->guestPaymentInformationManagement
                ->savePaymentInformationAndPlaceOrder($cartId, $email, $paymentMethod, $billingAddress);
        }
        $this->paymentRateLimiter->limit();
        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $quote = $this->quote->load($quoteIdMask->getQuoteId());
            if ($billingAddress !== null) {
                $customerEmail = $quote->getCustomerEmail() ?: $email;
                $this->orderManagement->billingAddressValidate($billingAddress, $customerEmail);
            }
            $order = $this->orderManagement->placeInitialOrder($quote, $email);
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

        $this->publishMessage($order, $cartId, $email, $paymentMethod, $billingAddress);

        return $order->getEntityId();
    }

    /**
     * Publish message
     *
     * @param OrderInterface $order
     * @param string $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return void
     */
    private function publishMessage(
        OrderInterface $order,
        string $cartId,
        string $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        $asyncOrder = $this->asyncOrderFactory->create();

        if ($billingAddress) {
            $asyncOrder->setAddress($billingAddress);
        }
        $asyncOrder->setCartId($cartId);
        $asyncOrder->setEmail($email);
        $asyncOrder->setIsGuest(true);
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
