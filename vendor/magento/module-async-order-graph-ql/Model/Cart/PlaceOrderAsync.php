<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrderGraphQl\Model\Cart;

use Magento\AsyncOrder\Api\AsyncPaymentInformationCustomerPublisherInterface as CustomerPublisher;
use Magento\AsyncOrder\Api\AsyncPaymentInformationGuestPublisherInterface as GuestPublisher;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\SubmitQuoteValidator;
use Magento\QuoteGraphQl\Model\Cart\PlaceOrder;

/**
 * Place order asynchronously if AsyncOrder is enabled
 */
class PlaceOrderAsync extends PlaceOrder
{
    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentManagement;

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
     * @param PaymentMethodManagementInterface $paymentManagement
     * @param CartManagementInterface $cartManagement
     * @param DeploymentConfig $deploymentConfig
     * @param SubmitQuoteValidator $submitQuoteValidator
     * @param CustomerPublisher $customerPublisher
     * @param GuestPublisher $guestPublisher
     */
    public function __construct(
        PaymentMethodManagementInterface $paymentManagement,
        CartManagementInterface $cartManagement,
        DeploymentConfig $deploymentConfig,
        SubmitQuoteValidator $submitQuoteValidator,
        CustomerPublisher $customerPublisher,
        GuestPublisher $guestPublisher
    ) {
        parent::__construct($paymentManagement, $cartManagement);
        $this->paymentManagement = $paymentManagement;
        $this->deploymentConfig = $deploymentConfig;
        $this->submitQuoteValidator = $submitQuoteValidator;
        $this->customerPublisher = $customerPublisher;
        $this->guestPublisher = $guestPublisher;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        Quote $cart,
        string $maskedCartId,
        int $userId,
        ?PaymentInterface $paymentMethod = null
    ): int {
        $asyncEnabled = $this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH);

        if (!$asyncEnabled) {
            return parent::execute($cart, $maskedCartId, $userId);
        }

        $cartId = (int)$cart->getId();

        $paymentMethod = $paymentMethod ?? $this->paymentManagement->get($cartId);
        if ($paymentMethod) {
            $paymentMethod->setChecks(
                [
                    MethodInterface::CHECK_USE_CHECKOUT,
                    MethodInterface::CHECK_USE_FOR_COUNTRY,
                    MethodInterface::CHECK_USE_FOR_CURRENCY,
                    MethodInterface::CHECK_ORDER_TOTAL_MIN_MAX,
                    MethodInterface::CHECK_ZERO_TOTAL
                ]
            );
            $cart->getPayment()->importData($paymentMethod->getData());
        }

        $billingAddress = $cart->getBillingAddress();
        $billingAddress->setId(null);
        $billingAddress->unsetData('extension_attributes');

        $this->submitQuoteValidator->validateQuote($cart);

        if ($paymentMethod === null) {
            throw new LocalizedException(__('Enter a valid payment method and try again.'));
        }

        if ($userId === 0) {
            $guestEmail = $cart->getCustomerEmail();
            $orderId = $this->guestPublisher->savePaymentInformationAndPlaceOrder(
                $maskedCartId,
                $guestEmail,
                $paymentMethod,
                $billingAddress
            );
        } else {
            $orderId = $this->customerPublisher->savePaymentInformationAndPlaceOrder(
                (string)$cartId,
                $paymentMethod,
                $billingAddress
            );
        }

        return (int)$orderId;
    }
}
