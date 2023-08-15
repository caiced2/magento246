<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AsyncOrder\Model;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\ShippingAddressManagementInterface;
use Magento\Checkout\Api\TotalsInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\AsyncOrder\Model\AsyncPaymentInformationCustomerPublisher;
use Magento\AsyncOrder\Model\OrderManagement;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AsyncPaymentInformationCustomerPublisherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CartItemRepositoryInterface
     */
    private $cartItemRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var ShippingAddressManagementInterface
     */
    private $shippingAddressManagement;

    /**
     * @var TotalsInformationManagementInterface
     */
    private $totalsInformationManagement;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->checkoutSession = $this->objectManager->create(Session::class);
        $this->cartRepository = $this->objectManager->create(CartRepositoryInterface::class);
        $this->cartManagement = $this->objectManager->create(CartManagementInterface::class);
        $this->cartItemRepository = $this->objectManager->create(CartItemRepositoryInterface::class);
        $this->orderRepository = $this->objectManager->create(OrderRepositoryInterface::class);
        $this->paymentMethodManagement = $this->objectManager->create(PaymentMethodManagementInterface::class);
        $this->totalsInformationManagement = $this->objectManager->create(TotalsInformationManagementInterface::class);
        $this->shippingAddressManagement = $this->objectManager->create(ShippingAddressManagementInterface::class);
    }

    /**
     * Expected - Order fail with exception.
     *
     * @magentoDataFixture Magento/AsyncOrder/_files/async_mode.php
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDbIsolation enabled
     * @dataProvider testDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBeforeSavePaymentInformationAndPlaceOrder(
        bool $withBillingAddress,
        bool $issueInCustomerEmail
    ): void {
        $customerId = 1;
        $carrierCode = 'flatrate';
        $shippingMethodCode = 'flatrate';
        $paymentMethod = 'checkmo';
        if ($issueInCustomerEmail) {
            $customerEmail = null;
        } else {
            $customerEmail = 'customer@example.com';
        }
        $product = $this->getProduct(1);
        $shippingAddress = $this->getShippingAddress();
        if ($withBillingAddress) {
            $billingAddress = $this->getBillingAddress();
        } else {
            $billingAddress = null;
        }
        $payment = $this->getPayment($paymentMethod);

        //Create cart and add product to it
        $cartId = $this->cartManagement->createEmptyCartForCustomer($customerId);
        $this->addProductToCart($product, $cartId);

        //Assign shipping address
        $this->shippingAddressManagement->assign($cartId, $shippingAddress);
        $shippingAddress = $this->shippingAddressManagement->get($cartId);

        //Calculate totals
        $totals = $this->getTotals($shippingAddress, $carrierCode, $shippingMethodCode);
        $this->totalsInformationManagement->calculate($cartId, $totals);

        //Set payment method
        $this->paymentMethodManagement->set($cartId, $payment);

        //Verify checkout session contains correct quote data
        $quote = $this->cartRepository->get($cartId);
        $quote->setCustomerEmail($customerEmail);
        $quoteId = $quote->getId();
        $this->cartRepository->save($quote);
        $this->checkoutSession->clearQuote();
        $this->checkoutSession->setQuoteId($quote->getId());

        //Get quote Id to pass to payment manager
        //$quoteId = $this->checkoutSession->getQuote()->getId();

        /** @var AsyncPaymentInformationCustomerPublisher $asyncPaymentInformationCustomerPublisher */
        $asyncPaymentInformationCustomerPublisher = $this->objectManager->create(
            AsyncPaymentInformationCustomerPublisher::class
        );

        try {
            $orderId = $asyncPaymentInformationCustomerPublisher->savePaymentInformationAndPlaceOrder(
                $quoteId,
                $payment,
                $billingAddress
            );
            $this->assertNotNull($orderId);

            $order = $this->orderRepository->get($orderId);
            if (!$issueInCustomerEmail || !$withBillingAddress) {
                $this->assertEquals(
                    OrderManagement::STATUS_RECEIVED,
                    $order->getStatus(),
                    'The current order has the wrong status '
                );
            } else {
                $this->assertEquals(
                    __("Failed address validation:\n%1", implode("\n", ['Email has a wrong format'])),
                    'Order processed'
                );
            }
        } catch (\Magento\Framework\Exception\CouldNotSaveException $e) {
            if ($issueInCustomerEmail) {
                $this->assertEquals(
                    __("Failed address validation:\n%1", implode("\n", ['Email has a wrong format'])),
                    $e->getMessage()
                );
            } else {
                $this->assertEquals(
                    __(
                        "The order wasn't placed. "
                    ),
                    $e->getMessage()
                );
            }
        }
    }

    public function testDataProvider(): array
    {
        return [
            [
                'withBillingAddress' => true,
                'issueInCustomerEmail' => true
            ],
            [
                'withBillingAddress' => false,
                'issueInCustomerEmail' => true
            ],
            [
                'withBillingAddress' => true,
                'issueInCustomerEmail' => false
            ],
            [
                'withBillingAddress' => false,
                'issueInCustomerEmail' => false
            ]
        ];
    }

    /**
     * @param int $productId
     * @return ProductInterface
     */
    private function getProduct($productId)
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $product = $productRepository->getById($productId);
        $product->setOptions(null);
        $productRepository->save($product);
        return $product;
    }

    /**
     * @return AddressInterface
     */
    private function getShippingAddress()
    {
        $shippingAddress = $this->objectManager->create(AddressInterface::class);
        $shippingAddress->setFirstname('MyFirstName');
        $shippingAddress->setLastname('MyLastName');
        $shippingAddress->setStreet('MyStreet');
        $shippingAddress->setCity('MyCity');
        $shippingAddress->setTelephone('1234567890');
        $shippingAddress->setPostcode('12345');
        $shippingAddress->setRegionId(12);
        $shippingAddress->setCountryId('US');
        $shippingAddress->setSameAsBilling(false);
        return $shippingAddress;
    }

    /**
     * @return AddressInterface
     */
    private function getBillingAddress()
    {
        /** @var AddressInterface $billingAddress */
        $billingAddress = $this->objectManager->create(AddressInterface::class);
        $billingAddress->setFirstname('First');
        $billingAddress->setLastname('Last');
        $billingAddress->setStreet('Street');
        $billingAddress->setCity('City');
        $billingAddress->setTelephone('1234567890');
        $billingAddress->setPostcode('12345');
        $billingAddress->setRegionId(12);
        $billingAddress->setCountryId('US');
        return $billingAddress;
    }

    /**
     * @param $paymentMethod
     *
     * @return PaymentInterface
     */
    private function getPayment($paymentMethod)
    {
        $payment = $this->objectManager->create(PaymentInterface::class);
        $payment->setMethod($paymentMethod);

        return $payment;
    }

    /**
     * @param ProductInterface $product
     * @param string $cartId
     */
    private function addProductToCart($product, $cartId)
    {
        /** @var CartItemInterface $quoteItem */
        $quoteItem = $this->objectManager->create(CartItemInterface::class);
        $quoteItem->setQuoteId($cartId);
        $quoteItem->setProduct($product);
        $quoteItem->setQty(2);
        $this->cartItemRepository->save($quoteItem);
    }

    /**
     * @param AddressInterface $shippingAddress
     * @param string $carrierCode
     * @param string $methodCode
     * @return TotalsInformationInterface
     */
    private function getTotals($shippingAddress, $carrierCode, $methodCode)
    {
        /** @var TotalsInformationInterface $totals */
        $totals = $this->objectManager->create(TotalsInformationInterface::class);
        $totals->setAddress($shippingAddress);
        $totals->setShippingCarrierCode($carrierCode);
        $totals->setShippingMethodCode($methodCode);

        return $totals;
    }
}
