<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Model;

use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Quote\Model\Quote\Address\ToOrder as ToOrderConverter;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemConverter;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment as ToOrderPaymentConverter;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\AsyncOrder\Model\Order\Email\Sender\RejectedOrderSender;
use Magento\SalesRule\Model\Coupon\Quote\UpdateCouponUsages;

/**
 * Class that can reject order in case something get wrong in the consumer.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderRejecter
{
    /**
     * Const for a new order status
     */
    public const STATUS_REJECTED = 'rejected';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ToOrderPaymentConverter
     */
    private $quotePaymentToOrderPayment;

    /**
     * @var ToOrderAddressConverter
     */
    private $quoteAddressToOrderAddress;

    /**
     * @var ToOrderConverter
     */
    private $quoteAddressToOrder;

    /**
     * @var ToOrderItemConverter
     */
    private $quoteItemToOrderItem;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var UpdateCouponUsages
     */
    private $updateCouponUsages;

    /**
     * @var RejectedOrderSender
     */
    private $rejectedOrderSender;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderFactory $orderFactory
     * @param QuoteRepository $quoteRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param ToOrderPaymentConverter $quotePaymentToOrderPayment
     * @param ToOrderAddressConverter $quoteAddressToOrderAddress
     * @param ToOrderConverter $quoteAddressToOrder
     * @param ToOrderItemConverter $quoteItemToOrderItem
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param UpdateCouponUsages $updateCouponUsages
     * @param RejectedOrderSender $rejectedOrderSender
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderFactory $orderFactory,
        QuoteRepository $quoteRepository,
        DataObjectHelper $dataObjectHelper,
        ToOrderPaymentConverter $quotePaymentToOrderPayment,
        ToOrderAddressConverter $quoteAddressToOrderAddress,
        ToOrderConverter $quoteAddressToOrder,
        ToOrderItemConverter $quoteItemToOrderItem,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        UpdateCouponUsages $updateCouponUsages,
        RejectedOrderSender $rejectedOrderSender
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->quotePaymentToOrderPayment = $quotePaymentToOrderPayment;
        $this->quoteAddressToOrderAddress = $quoteAddressToOrderAddress;
        $this->quoteAddressToOrder = $quoteAddressToOrder;
        $this->quoteItemToOrderItem = $quoteItemToOrderItem;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->updateCouponUsages = $updateCouponUsages;
        $this->rejectedOrderSender = $rejectedOrderSender;
    }

    /**
     * Reject Async Order
     *
     * @param AsyncOrderMessageInterface $asyncOrderMessage
     * @param string $rejectComment
     * @throws NoSuchEntityException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function reject(
        AsyncOrderMessageInterface $asyncOrderMessage,
        $rejectComment
    ): void {
        /** @var Order $order */
        $order = $this->orderFactory->create();

        /** @var $quoteIdMask QuoteIdMask */
        if ($asyncOrderMessage->getIsGuest()) {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($asyncOrderMessage->getCartId(), 'masked_id');
            $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
        } else {
            $quote = $this->quoteRepository->get($asyncOrderMessage->getCartId());
        }

        if ($quote->isVirtual()) {
            $this->dataObjectHelper->mergeDataObjects(
                OrderInterface::class,
                $order,
                $this->quoteAddressToOrder->convert($quote->getBillingAddress())
            );
        } else {
            $this->dataObjectHelper->mergeDataObjects(
                OrderInterface::class,
                $order,
                $this->quoteAddressToOrder->convert($quote->getShippingAddress())
            );

            $quoteShippingAddress = $this->quoteAddressToOrderAddress->convert(
                $quote->getShippingAddress(),
                [
                    'address_type' => 'shipping',
                    'email' => $quote->getCustomerEmail()
                ]
            );
            $quoteShippingAddress->setData('quote_address_id', $quote->getShippingAddress()->getId());
            $addresses[] = $quoteShippingAddress;
            $order->setShippingAddress($quoteShippingAddress);
            $order->setShippingMethod($quote->getShippingAddress()->getShippingMethod());
        }

        $quoteBillingAddress = $this->quoteAddressToOrderAddress->convert(
            $quote->getBillingAddress(),
            [
                'address_type' => 'billing',
                'email' => $quote->getCustomerEmail()
            ]
        );
        $quoteBillingAddress->setData('quote_address_id', $quote->getBillingAddress()->getId());
        $addresses[] = $quoteBillingAddress;

        //set quote addresses to order
        $order->setBillingAddress($quoteBillingAddress);
        $order->setAddresses($addresses);
        $order->setPayment($this->quotePaymentToOrderPayment->convert($quote->getPayment()));
        $order->setItems($this->resolveItems($quote));
        if ($quote->getCustomer() && $quote->getCustomer()->getId()) {
            $order->setCustomerId($quote->getCustomer()->getId());
        }
        $order->setQuoteId($quote->getId());
        if ($quote->getCustomerEmail()) {
            $order->setCustomerEmail($quote->getCustomerEmail());
        } else {
            $order->setCustomerEmail($asyncOrderMessage->getEmail());
        }

        if ($quote->getCustomerFirstname()) {
            $order->setCustomerFirstname($quote->getCustomerFirstname());
        } else {
            $order->setCustomerFirstname($quoteBillingAddress->getFirstname());
        }
        if ($quote->getCustomerMiddlename()) {
            $order->setCustomerMiddlename($quote->getCustomerMiddlename());
        } else {
            $order->setCustomerMiddlename($quoteBillingAddress->getMiddlename());
        }
        if ($quote->getCustomerLastname()) {
            $order->setCustomerLastname($quote->getCustomerLastname());
        } else {
            $order->setCustomerLastname($quoteBillingAddress->getLastname());
        }

        if ($quote->getOrigOrderId()) {
            $order->setEntityId($quote->getOrigOrderId());
        }

        if ($quote->getReservedOrderId()) {
            $order->setIncrementId($quote->getReservedOrderId());
        }

        $order->setStatus(self::STATUS_REJECTED);
        $order->setState(Order::STATE_CANCELED);
        $order->addCommentToStatusHistory($rejectComment, self::STATUS_REJECTED, false);

        $this->orderRepository->save($order);
        $this->updateCouponUsages->execute($quote, false);
        $this->rejectedOrderSender->send($order, true, $rejectComment);
    }

    /**
     * Convert quote items to order items for quote
     *
     * @param Quote $quote
     * @return array
     */
    private function resolveItems(QuoteEntity $quote)
    {
        $orderItems = [];
        foreach ($quote->getAllItems() as $quoteItem) {
            $itemId = $quoteItem->getId();

            if (!empty($orderItems[$itemId])) {
                continue;
            }

            $parentItemId = $quoteItem->getParentItemId();
            /** @var \Magento\Quote\Model\ResourceModel\Quote\Item $parentItem */
            if ($parentItemId && !isset($orderItems[$parentItemId])) {
                $orderItems[$parentItemId] = $this->quoteItemToOrderItem->convert(
                    $quoteItem->getParentItem(),
                    ['parent_item' => null]
                );
            }
            $parentItem = isset($orderItems[$parentItemId]) ? $orderItems[$parentItemId] : null;
            $orderItems[$itemId] = $this->quoteItemToOrderItem->convert($quoteItem, ['parent_item' => $parentItem]);
        }
        return array_values($orderItems);
    }
}
