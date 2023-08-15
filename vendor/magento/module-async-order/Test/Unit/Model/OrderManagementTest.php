<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Test\Unit\Model;

use Psr\Log\LoggerInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\AsyncOrder\Model\Order;
use Magento\AsyncOrder\Model\Quote;
use Magento\AsyncOrder\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\AsyncOrder\Model\ResourceModel\Order as OrderResourceModel;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SalesSequence\Model\Manager as SalesSequenceManager;
use Magento\Framework\DB\Sequence\SequenceInterface;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderManagementTest extends TestCase
{
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
     * @var OrderManagement
     */
    private $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->orderResourceModel = $this->createMock(OrderResourceModel::class);
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setLastQuoteId',
                    'setLastSuccessQuoteId',
                    'setLastOrderId',
                    'setLastRealOrderId',
                    'setLastOrderStatus',
                ]
            )
            ->getMock();
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->sequenceManager = $this->createMock(SalesSequenceManager::class);

        $this->model = $objectManager->getObject(
            OrderManagement::class,
            [
                'orderFactory' => $this->orderFactory,
                'orderResourceModel' => $this->orderResourceModel,
                'checkoutSession' => $this->checkoutSession,
                'logger' => $this->logger,
                'sequenceManager' => $this->sequenceManager,
                'paymentMethods' => []
            ]
        );
    }

    /**
     * @param string $email
     * @param int $customerId
     * @param int $quoteId
     * @dataProvider processDataProvider
     */
    public function testProcess(string $email, int $customerId, int $quoteId): void
    {
        $grandTotal = 100500;
        $storeId = 2;
        $itemsCount = 3;
        $sequenceNextValue = '00002';
        $baseCurrencyCode = 'baseCurrencyCode';
        $globalCurrencyCode = 'globalCurrencyCode';
        $orderCurrencyCode = 'quoteCurrencyCode';
        $storeCurrencyCode = 'storeCurrencyCode';

        $sequence = $this->getMockForAbstractClass(SequenceInterface::class);
        $sequence->expects($this->once())->method('getNextValue')->willReturn($sequenceNextValue);

        $this->sequenceManager->expects($this->once())->method('getSequence')->willReturn($sequence);

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getGrandTotal',
                    'getBaseCurrencyCode',
                    'getGlobalCurrencyCode',
                    'getQuoteCurrencyCode',
                    'getStoreCurrencyCode',
                    'getCustomerId',
                    'getId',
                    'getItemsCount'
                ]
            )->getMock();

        $quote->expects($this->once())->method('getGrandTotal')->willReturn($grandTotal);
        $quote->expects($this->once())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $quote->expects($this->once())->method('getGlobalCurrencyCode')->willReturn($globalCurrencyCode);
        $quote->expects($this->once())->method('getQuoteCurrencyCode')->willReturn($orderCurrencyCode);
        $quote->expects($this->once())->method('getStoreCurrencyCode')->willReturn($storeCurrencyCode);
        $quote->expects($this->atLeastOnce())->method('getCustomerId')->willReturn($customerId);
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);
        $quote->expects($this->atLeastOnce())->method('getItemsCount')->willReturn($itemsCount);

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId', 'getGroup'])
            ->getMock();
        $store->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $group = $this->createMock(Group::class);
        $store->expects($this->once())->method('getGroup')->willReturn($group);

        $order = $this->createMock(Order::class);

        $order->expects($this->once())->method('setGrandTotal')->with($grandTotal)->willReturnSelf();
        $order->expects($this->once())->method('setBaseCurrencyCode')->with($baseCurrencyCode)->willReturnSelf();
        $order->expects($this->once())->method('setGlobalCurrencyCode')->with($globalCurrencyCode)->willReturnSelf();
        $order->expects($this->once())->method('setOrderCurrencyCode')->with($orderCurrencyCode)->willReturnSelf();
        $order->expects($this->once())->method('setStoreCurrencyCode')->with($storeCurrencyCode)->willReturnSelf();

        if ($email) {
            $order->expects($this->once())->method('setCustomerEmail')->with($email)->willReturnSelf();
        } else {
            $order->expects($this->never())->method('setCustomerEmail');
        }
        if ($customerId) {
            $order->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        } else {
            $order->expects($this->never())->method('setCustomerId');
        }
        if ($quoteId) {
            $order->expects($this->once())->method('setQuoteId')->with($quoteId)->willReturnSelf();
        } else {
            $order->expects($this->never())->method('setQuoteId');
        }

        $order->expects($this->once())->method('getStore')->willReturn($store);
        $order->expects($this->once())->method('setStoreId')->with($storeId)->willReturnSelf();
        $order->expects($this->once())->method('setStatus')->with(OrderManagement::STATUS_RECEIVED)->willReturnSelf();
        $order->expects($this->once())->method('setIncrementId')->with($sequenceNextValue)->willReturnSelf();
        $order->expects($this->once())->method('setTotalItemCount')->with($itemsCount)->willReturnSelf();
        $order->expects($this->once())->method('setProtectCode')->willReturnSelf();

        $this->orderFactory->expects($this->once())->method('create')->willReturn($order);
        $this->orderResourceModel->expects($this->once())->method('save')->with($order)->willReturnSelf();

        $this->assertEquals($order, $this->model->placeInitialOrder($quote, $email));
    }

    public function testProcessQuoteWithInitialOrder(): void
    {
        $quoteId = 10;
        $orderId = 5;
        $realOrderId = '000005';
        $status = 'status';

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['setIsActive', 'setOrigOrderId', 'setReservedOrderId', 'getId', 'save'])
            ->getMock();

        $quote->expects($this->once())->method('setIsActive')->with(false)->willReturnSelf();
        $quote->expects($this->once())->method('setOrigOrderId')->with($orderId)->willReturnSelf();
        $quote->expects($this->once())->method('setReservedOrderId')->with($realOrderId)->willReturnSelf();
        $quote->expects($this->once())->method('save')->willReturnSelf();
        $quote->expects($this->atLeastOnce())->method('getId')->willReturn($quoteId);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityId', 'getIncrementId', 'getStatus'])
            ->getMock();

        $order->expects($this->atLeastOnce())->method('getEntityId')->willReturn($orderId);
        $order->expects($this->atLeastOnce())->method('getIncrementId')->willReturn($realOrderId);
        $order->expects($this->atLeastOnce())->method('getStatus')->willReturn($status);

        $this->checkoutSession->expects(
            $this->once()
        )->method('setLastQuoteId')->with(
            $quoteId
        )->willReturnSelf();

        $this->checkoutSession->expects(
            $this->once()
        )->method('setLastSuccessQuoteId')->with(
            $quoteId
        )->willReturnSelf();

        $this->checkoutSession->expects(
            $this->once()
        )->method('setLastOrderId')->with(
            $orderId
        )->willReturnSelf();

        $this->checkoutSession->expects(
            $this->once()
        )->method('setLastRealOrderId')->with(
            $realOrderId
        )->willReturnSelf();

        $this->checkoutSession->expects(
            $this->once()
        )->method('setLastOrderStatus')->with(
            $status
        )->willReturnSelf();

        $this->model->processQuoteWithInitialOrder($quote, $order);
    }

    public function processDataProvider(): array
    {
        return [
            [
                'email' => 'test@example.com',
                'customerId' => 0,
                'quoteId' => 0,
            ],
            [
                'email' => '',
                'customerId' => 3,
                'quoteId' => 5,
            ]
        ];
    }
}
