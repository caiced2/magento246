<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrderGraphQl\Model\Cart;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\Payment\PaymentMethodBuilder;
use Magento\QuoteGraphQl\Model\Cart\SetPaymentMethodOnCart;
use Magento\QuoteGraphQl\Model\Cart\PlaceOrder as PlaceOrderSynchronous;
use Magento\QuoteGraphQl\Model\Cart\SetPaymentAndPlaceOrder;

/**
 * Set payment method and place order asynchronously if AsyncOrder is enabled
 */
class SetPaymentAndPlaceOrderAsync extends SetPaymentAndPlaceOrder
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var PaymentMethodBuilder
     */
    private $paymentMethodBuilder;

    /**
     * @var PlaceOrderAsync
     */
    private $placeOrderAsync;

    /**
     * @param SetPaymentMethodOnCart $setPaymentMethod
     * @param PlaceOrderSynchronous $placeOrderSync
     * @param DeploymentConfig $deploymentConfig
     * @param PaymentMethodBuilder $paymentMethodBuilder
     * @param PlaceOrderAsync $placeOrderAsync
     */
    public function __construct(
        SetPaymentMethodOnCart $setPaymentMethod,
        PlaceOrderSynchronous $placeOrderSync,
        DeploymentConfig $deploymentConfig,
        PaymentMethodBuilder $paymentMethodBuilder,
        PlaceOrderAsync $placeOrderAsync
    ) {
        parent::__construct($setPaymentMethod, $placeOrderSync);
        $this->deploymentConfig = $deploymentConfig;
        $this->paymentMethodBuilder = $paymentMethodBuilder;
        $this->placeOrderAsync = $placeOrderAsync;
    }

    /**
     * @inheritDoc
     */
    public function execute(Quote $cart, string $maskedCartId, int $userId, array $paymentData): int
    {
        $asyncEnabled = $this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH);

        if (!$asyncEnabled) {
            return parent::execute($cart, $maskedCartId, $userId, $paymentData);
        }

        if (!$cart->isVirtual()) {
            $shippingAddress = $cart->getShippingAddress();
            if ($shippingAddress->getCountryId() === null) {
                throw new GraphQlInputException(
                    __('The shipping address is missing. Set the address and try again.')
                );
            }
        }

        $paymentMethod = $this->paymentMethodBuilder->build($paymentData);
        return $this->placeOrderAsync->execute($cart, $maskedCartId, $userId, $paymentMethod);
    }
}
