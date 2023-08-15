<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Test\Unit\Model;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote;
use \Magento\Customer\Api\Data\CustomerInterface;
use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterface;
use Magento\AsyncOrder\Model\Order\Email\Sender\RejectedOrderSender;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Quote\Model\Quote\Address\ToOrder as ToOrderConverter;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemConverter;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment as ToOrderPaymentConverter;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\AsyncOrder\Model\OrderRejecter;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\Coupon\Quote\UpdateCouponUsages;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderRejecterTest extends TestCase
{
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
     * @var OrderRejecter
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->orderRepository = $this->getMockForAbstractClass(OrderRepositoryInterface::class);
        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->quoteRepository = $this->createMock(QuoteRepository::class);
        $this->dataObjectHelper = $this->createMock(DataObjectHelper::class);
        $this->quotePaymentToOrderPayment = $this->createMock(ToOrderPaymentConverter::class);
        $this->quoteAddressToOrderAddress = $this->createMock(ToOrderAddressConverter::class);
        $this->quoteAddressToOrder = $this->createMock(ToOrderConverter::class);
        $this->quoteItemToOrderItem = $this->createMock(ToOrderItemConverter::class);
        $this->quoteIdMaskFactory = $this->getMockBuilder(QuoteIdMaskFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->updateCouponUsages = $this->createMock(UpdateCouponUsages::class);
        $this->rejectedOrderSender = $this->createMock(RejectedOrderSender::class);

        $this->model = $objectManager->getObject(
            OrderRejecter::class,
            [
                'orderRepository' => $this->orderRepository,
                'orderFactory' => $this->orderFactory,
                'quoteRepository' => $this->quoteRepository,
                'dataObjectHelper' => $this->dataObjectHelper,
                'quotePaymentToOrderPayment' => $this->quotePaymentToOrderPayment,
                'quoteAddressToOrderAddress' => $this->quoteAddressToOrderAddress,
                'quoteAddressToOrder' => $this->quoteAddressToOrder,
                'quoteItemToOrderItem' => $this->quoteItemToOrderItem,
                'quoteIdMaskFactory' => $this->quoteIdMaskFactory,
                'updateCouponUsages' => $this->updateCouponUsages,
                'rejectedOrderSender' => $this->rejectedOrderSender
            ]
        );
    }

    /**
     * @param bool $isGuest
     * @param bool $isVirtual
     * @param bool $isEmptyCustomerData
     * @dataProvider rejectDataProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testReject(bool $isGuest, bool $isVirtual, bool $isEmptyCustomerData): void
    {
        $cartId = 'cart_id';
        $customerEmail = 'email@example.com';
        $quoteId = 123;
        $billingAddressId = 111;
        $customerId = 444;

        $asyncOrderMessage = $this->getMockForAbstractClass(AsyncOrderMessageInterface::class);
        $rejectComment = 'Order Message';

        $order = $this->createMock(Order::class);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'isVirtual',
                    'getCustomerEmail',
                    'getCustomerFirstname',
                    'getCustomerMiddlename',
                    'getCustomerLastname',
                    'getPayment',
                    'getBillingAddress',
                    'getAllItems',
                    'getCustomer',
                    'getShippingAddress'
                ]
            )
            ->getMock();

        $this->orderFactory->expects($this->once())->method('create')->willReturn($order);

        $asyncOrderMessage->expects($this->once())->method('getIsGuest')->willReturn($isGuest);

        if ($isGuest) {
            $quoteIdMask = $this->getMockBuilder(QuoteIdMask::class)
                ->disableOriginalConstructor()
                ->setMethods(['load', 'getQuoteId'])
                ->getMock();

            $this->quoteIdMaskFactory->expects($this->once())->method('create')->willReturn($quoteIdMask);

            $asyncOrderMessage->expects($this->once())->method('getCartId')->willReturn($cartId);
            $quoteIdMask->expects($this->once())->method('load')->with($cartId, 'masked_id')->willReturnSelf();

            $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($quote);
            $quoteIdMask->expects($this->once())->method('getQuoteId')->willReturn($quoteId);
        } else {
            $this->quoteRepository->expects($this->once())->method('get')->with($quoteId)->willReturn($quote);
            $asyncOrderMessage->expects($this->once())->method('getCartId')->willReturn($quoteId);
        }

        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);

        $quote->expects($this->once())->method('isVirtual')->willReturn($isVirtual);
        if ($isEmptyCustomerData) {
            $quote->expects($this->atLeastOnce())->method('getCustomerEmail')->willReturn(null);
        } else {
            $quote->expects($this->atLeastOnce())->method('getCustomerEmail')->willReturn($customerEmail);
        }
        $quote->expects($this->once())->method('getAllItems')->willReturn([]);
        $quote->expects($this->atLeastOnce())->method('getCustomer')->willReturn($customer);
        $billingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $quote->expects($this->atLeastOnce())->method('getBillingAddress')->willReturn($billingAddress);

        if ($isVirtual) {
            $orderFromQuote = $this->createMock(Order::class);

            $this->quoteAddressToOrder->expects($this->once())->method('convert')->with($billingAddress)->willReturn(
                $orderFromQuote
            );

            $this->dataObjectHelper->expects($this->once())->method('mergeDataObjects')->with(
                OrderInterface::class,
                $order,
                $orderFromQuote
            )->willReturnSelf();

            $orderAddress = $this->getMockBuilder(OrderAddressInterface::class)
                ->addMethods(['setData'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

            if ($isEmptyCustomerData) {
                $this->quoteAddressToOrderAddress->expects($this->once())->method('convert')->with(
                    $billingAddress,
                    [
                        'address_type' => 'billing',
                        'email' => null
                    ]
                )->willReturn($orderAddress);
            } else {
                $this->quoteAddressToOrderAddress->expects($this->once())->method('convert')->with(
                    $billingAddress,
                    [
                        'address_type' => 'billing',
                        'email' => $customerEmail
                    ]
                )->willReturn($orderAddress);
            }

            $billingAddress->expects($this->once())->method('getId')->willReturn($billingAddressId);
            $orderAddress->expects(
                $this->once()
            )->method('setData')->with('quote_address_id', $billingAddressId)->willReturnSelf();
        } else {
            $shippingAddress = $this->getMockBuilder(Address::class)
                ->disableOriginalConstructor()
                ->setMethods(['getId'])
                ->getMock();
            $quote->expects($this->atLeastOnce())->method('getShippingAddress')->willReturn($shippingAddress);

            $orderFromQuote = $this->createMock(Order::class);

            $this->quoteAddressToOrder->expects($this->once())->method('convert')->with($shippingAddress)->willReturn(
                $orderFromQuote
            );

            $this->dataObjectHelper->expects($this->once())->method('mergeDataObjects')->with(
                OrderInterface::class,
                $order,
                $orderFromQuote
            )->willReturnSelf();

            $orderAddress = $this->getMockBuilder(OrderAddressInterface::class)
                ->addMethods(['setData'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

            $this->quoteAddressToOrderAddress->expects($this->atLeastOnce())->method('convert')->willReturn(
                $orderAddress
            );

            $orderAddress->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
            $order->expects($this->once())->method('setShippingAddress')->willReturnSelf();
            $order->expects($this->once())->method('setShippingMethod')->willReturnSelf();
        }

        $payment = $this->createMock(Payment::class);
        $quote->expects($this->atLeastOnce())->method('getPayment')->willReturn($payment);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);

        $this->quotePaymentToOrderPayment->expects($this->once())->method('convert')->with($payment)->willReturn(
            $orderPayment
        );

        $order->expects($this->once())->method('setBillingAddress')->willReturnSelf();
        $order->expects($this->once())->method('setPayment')->willReturnSelf();
        $order->expects($this->once())->method('setItems')->willReturnSelf();
        $order->expects($this->once())->method('setCustomerId')->willReturnSelf();
        $order->expects($this->once())->method('setQuoteId')->willReturnSelf();

        if ($isEmptyCustomerData) {
            $customerEmailFromMessage = 'email2@example.com';
            $asyncOrderMessage->expects($this->once())->method('getEmail')->willReturn($customerEmailFromMessage);
            $order->expects(
                $this->once()
            )->method('setCustomerEmail')->with($customerEmailFromMessage)->willReturnSelf();

            $customerFirstname = 'customerFirstname';
            $quote->expects($this->once())->method('getCustomerFirstname')->willReturn(null);
            $orderAddress->expects($this->once())->method('getFirstname')->willReturn($customerFirstname);

            $order->expects(
                $this->once()
            )->method('setCustomerFirstname')->with($customerFirstname)->willReturnSelf();

            $customerMiddlename = 'customerMiddlename';
            $quote->expects($this->once())->method('getCustomerMiddlename')->willReturn(null);
            $orderAddress->expects($this->once())->method('getMiddlename')->willReturn($customerMiddlename);

            $order->expects(
                $this->once()
            )->method('setCustomerMiddlename')->with($customerMiddlename)->willReturnSelf();

            $customerLastname = 'customerLastname';
            $quote->expects($this->once())->method('getCustomerLastname')->willReturn(null);
            $orderAddress->expects($this->once())->method('getLastname')->willReturn($customerLastname);

            $order->expects(
                $this->once()
            )->method('setCustomerLastname')->with($customerLastname)->willReturnSelf();
        } else {
            $order->expects($this->once())->method('setCustomerEmail')->with($customerEmail)->willReturnSelf();

            $customerFirstname = 'customerFirstname';
            $quote->expects($this->atLeastOnce())->method('getCustomerFirstname')->willReturn($customerFirstname);
            $order->expects(
                $this->once()
            )->method('setCustomerFirstname')->with($customerFirstname)->willReturnSelf();

            $customerMiddlename = 'customerMiddlename';
            $quote->expects($this->atLeastOnce())->method('getCustomerMiddlename')->willReturn($customerMiddlename);
            $order->expects(
                $this->once()
            )->method('setCustomerMiddlename')->with($customerMiddlename)->willReturnSelf();

            $customerLastname = 'customerLastname';
            $quote->expects($this->atLeastOnce())->method('getCustomerLastname')->willReturn($customerLastname);
            $order->expects(
                $this->once()
            )->method('setCustomerLastname')->with($customerLastname)->willReturnSelf();
        }

        $order->expects($this->once())->method('setStatus')->with(OrderRejecter::STATUS_REJECTED)->willReturnSelf();
        $order->expects($this->once())->method('setState')->with(Order::STATE_CANCELED)->willReturnSelf();
        $order->expects($this->once())->method('addCommentToStatusHistory')->with(
            $rejectComment,
            OrderRejecter::STATUS_REJECTED,
            false
        )->willReturnSelf();

        $this->orderRepository->expects($this->once())->method('save')->with($order)->willReturnSelf();
        $this->updateCouponUsages->expects($this->once())->method('execute')->with($quote, false)->willReturnSelf();
        $this->rejectedOrderSender->expects($this->once())->method('send')->with(
            $order,
            true,
            $rejectComment
        )->willReturnSelf();

        $this->model->reject($asyncOrderMessage, $rejectComment);
    }

    public function rejectDataProvider(): array
    {
        return [
            [
                'isGuest' => true,
                'isVirtual' => true,
                'isEmptyCustomerData' => true,
            ],
            [
                'isGuest' => false,
                'isVirtual' => false,
                'isEmptyCustomerData' => true,
            ],
            [
                'isGuest' => true,
                'isVirtual' => false,
                'isEmptyCustomerData' => true,
            ],
            [
                'isGuest' => false,
                'isVirtual' => true,
                'isEmptyCustomerData' => true,
            ],
            [
                'isGuest' => true,
                'isVirtual' => true,
                'isEmptyCustomerData' => false,
            ],
            [
                'isGuest' => false,
                'isVirtual' => false,
                'isEmptyCustomerData' => false,
            ],
            [
                'isGuest' => true,
                'isVirtual' => false,
                'isEmptyCustomerData' => false,
            ],
            [
                'isGuest' => false,
                'isVirtual' => true,
                'isEmptyCustomerData' => false,
            ]
        ];
    }
}
