<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AsyncOrder\Model;

use Magento\AsyncOrder\Api\Data\OrderInterface;
use Magento\AsyncOrder\Model\ResourceModel\Order as OrderResourceModel;
use Magento\AsyncOrder\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;
use Magento\SalesSequence\Model\Manager as SalesSequenceManager;
use Magento\Store\Model\Store;
use Magento\AsyncOrder\Model\Quote\Address\Validator as AddressValidator;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Psr\Log\LoggerInterface;

/**
 * Async order management to create initial order with minimal set of attributes.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class OrderManagement
{
    /**
     * Constant for async option path.
     */
    public const ASYNC_ORDER_OPTION_PATH = 'checkout/async';

    /**
     * Initial order status it is set to queue.
     */
    public const STATUS_RECEIVED = 'received';

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderResourceModel
     */
    private $orderResourceModel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SalesSequenceManager
     */
    private $sequenceManager;

    /**
     * @var AddressValidator
     */
    private $addressValidator;

    /**
     * @var array
     */
    private $paymentMethods;

    /**
     * @var ToOrderAddressConverter
     */
    protected $quoteAddressToOrderAddress;

    /**
     * @param OrderFactory $orderFactory
     * @param OrderResourceModel $orderResourceModel
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     * @param SalesSequenceManager $sequenceManager
     * @param AddressValidator $addressValidator
     * @param ToOrderAddressConverter $quoteAddressToOrderAddress
     * @param array $paymentMethods
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderResourceModel $orderResourceModel,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger,
        SalesSequenceManager $sequenceManager,
        AddressValidator $addressValidator,
        ToOrderAddressConverter $quoteAddressToOrderAddress,
        array $paymentMethods
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderResourceModel = $orderResourceModel;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->sequenceManager = $sequenceManager;
        $this->addressValidator = $addressValidator;
        $this->paymentMethods = $paymentMethods;
        $this->quoteAddressToOrderAddress = $quoteAddressToOrderAddress;
    }

    /**
     * Place order with minimal set of attributes and clear quote.
     *
     * @param Quote $quote
     * @param string|null $email
     * @return OrderInterface
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function placeInitialOrder(Quote $quote, string $email = null): OrderInterface
    {
        try {
            $this->orderResourceModel->beginTransaction();
            $order = $this->orderFactory->create();
            $order->setGrandTotal($quote->getGrandTotal());
            $order->setBaseCurrencyCode($quote->getBaseCurrencyCode());
            $order->setGlobalCurrencyCode($quote->getGlobalCurrencyCode());
            $order->setOrderCurrencyCode($quote->getQuoteCurrencyCode());
            $order->setStoreCurrencyCode($quote->getStoreCurrencyCode());
            if ($email) {
                $order->setCustomerEmail($email);
            }
            if ($quote->getCustomerId()) {
                $order->setCustomerId($quote->getCustomerId());
            }
            if ($quote->getId()) {
                $order->setQuoteId($quote->getId());
            }
            /** @var Store $store */
            $store = $order->getStore();
            $order->setStoreId($store->getStoreId());
            $order->setStatus(self::STATUS_RECEIVED);

            if ($order->getIncrementId() === null) {
                $storeId = $store->getId();
                if ($storeId === null) {
                    $storeId = $store->getGroup()->getDefaultStoreId();
                }
                $order->setIncrementId(
                    $this->sequenceManager->getSequence(
                        $order->getEntityType(),
                        $storeId
                    )->getNextValue()
                );
            }

            $order->setTotalItemCount($quote->getItemsCount());
            $order->setProtectCode(
                substr(
                    hash(
                        'sha256',
                        uniqid((string)Random::getRandomNumber(), true) . ':' . microtime(true)
                    ),
                    5,
                    32
                )
            );

            $this->orderResourceModel->save($order);

            $this->orderResourceModel->commit();
        } catch (AlreadyExistsException $e) {
            $this->logger->critical(
                'Order already exists. ' . $e->getMessage()
            );
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical(
                'Saving order ' . $order->getIncrementId() . ' failed: ' . $e->getMessage()
            );
            throw $e;
        }

        return $order;
    }

    /**
     * Set quote inactive and set initial order data into checkout session.
     *
     * @param Quote $quote
     * @param OrderInterface $order
     * @return void
     * @throws \Exception
     */
    public function processQuoteWithInitialOrder(Quote $quote, OrderInterface $order): void
    {
        $quote->setIsActive(false);
        $quote->setOrigOrderId($order->getEntityId());
        $quote->setReservedOrderId($order->getIncrementId());
        $quote->save();

        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastOrderId($order->getEntityId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());
    }

    /**
     * Billing address validate.
     *
     * @param AddressInterface $billingAddress
     * @param string $email
     *
     * @return void
     * @throws LocalizedException
     */
    public function billingAddressValidate(AddressInterface $billingAddress, string $email = null): void
    {
        $address = $this->quoteAddressToOrderAddress->convert(
            $billingAddress,
            [
                'address_type' => 'billing',
                'email' => $email
            ]
        );
        $errors = $this->addressValidator->validate($address);
        if (!empty($errors)) {
            throw new LocalizedException(
                __("Failed address validation:\n%1", implode("\n", $errors))
            );
        }
    }

    /**
     * Get payment methods that only work in synchronous mode.
     *
     * @return array
     */
    public function getPaymentMethodsForSynchronousMode(): array
    {
        return $this->paymentMethods;
    }
}
