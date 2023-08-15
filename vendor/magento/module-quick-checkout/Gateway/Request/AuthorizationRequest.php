<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Gateway\Request;

use Laminas\Validator\Regex;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\QuickCheckout\Model\AddressService;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenSessionStorage;
use Magento\QuickCheckout\Model\NoHtmlValidator;
use Magento\QuickCheckout\Model\PaymentMethodService;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

/**
 * Authorization request payload
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class AuthorizationRequest implements BuilderInterface
{
    private const EMPTY_AMOUNT = 0;
    private const DEFAULT_PRODUCT_TYPE = 'unknown';

    /**
     * @var bool
     */
    private $autoCapture;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var NoHtmlValidator
     */
    private $noHtmlValidator;

    /**
     * @var AddressService
     */
    private $addressService;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var array
     */
    private $productMapping;

    /**
     * @var OauthTokenSessionStorage
     */
    private $tokenSessionStorage;

    /**
     * @param CountryFactory $countryFactory
     * @param PaymentMethodService $paymentMethodService
     * @param AddressService $addressService
     * @param ManagerInterface $messageManager
     * @param NoHtmlValidator $noHtmlValidator
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param array $productMapping
     * @param OauthTokenSessionStorage $tokenSessionStorage
     * @param bool $autoCapture
     */
    public function __construct(
        CountryFactory           $countryFactory,
        PaymentMethodService     $paymentMethodService,
        AddressService           $addressService,
        ManagerInterface         $messageManager,
        NoHtmlValidator          $noHtmlValidator,
        LoggerInterface          $logger,
        CartRepositoryInterface  $quoteRepository,
        array                    $productMapping,
        OauthTokenSessionStorage $tokenSessionStorage,
        bool                     $autoCapture = false
    ) {
        $this->countryFactory = $countryFactory;
        $this->paymentMethodService = $paymentMethodService;
        $this->addressService = $addressService;
        $this->messageManager = $messageManager;
        $this->noHtmlValidator = $noHtmlValidator;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->productMapping = $productMapping;
        $this->autoCapture = $autoCapture;
        $this->tokenSessionStorage = $tokenSessionStorage;
    }

    /**
     * Build request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObjectInterface $payment */
        $payment = SubjectReader::readPayment($buildSubject);

        $uri = '/v1/merchant/transactions/authorize';

        /** @var OrderInterface $order */
        $order = $payment->getPayment()->getOrder();

        /** @var OrderAddressInterface $billingAddress */
        $billingAddress = $order->getBillingAddress();
        /** @var OrderAddressInterface $shippingAddress */
        $shippingAddress = $order->getShippingAddress();
        /** @var string[] $additionalInformation */
        $additionalInformation = $payment->getPayment()->getAdditionalInformation();
        /** @var string[] $card */
        $card = json_decode($additionalInformation['card'], true);

        $error = $this->validateCreditCard($card);
        if ($error) {
            $this->logger->error(sprintf('Credit card information is incorrect, error %s', $error));
            throw new LocalizedException(__('Select a valid card or enter valid card information.'));
        }

        $shipments = $this->prepareShippingAddressInformation(
            $order,
            $shippingAddress,
            ($additionalInformation['shipping_address_id'] ?? ''),
            (bool)($additionalInformation['add_new_address'] ?? false)
        );

        $request = [
            'cart' => [
                'order_reference' => $order->getIncrementId(),
                'total_amount' => $this->formatAmount($order->getBaseGrandTotal()),
                'tax_amount' => $this->formatAmount($order->getBaseTaxAmount()),
                'currency' => $order->getBaseCurrencyCode(),
                'shipments' => $shipments,
            ],
            'user_identifier' => [
                'email' => $billingAddress->getEmail(),
                'phone' => $billingAddress->getTelephone()
            ],
            'user_identity' => [
                'first_name' => $billingAddress->getFirstname(),
                'last_name' => $billingAddress->getLastname(),
            ],
            'auto_capture' => $this->autoCapture,
            'display_id' => '',
        ];

        $cartItems = $this->getCartItems((int)$order->getQuoteId());

        if (count($cartItems) > 0) {
            $request['cart']['items'] = $cartItems;
        }

        if ($additionalInformation['is_card_new'] ?? true) {
            $request['source'] = 'direct_payments';
            $request['create_bolt_account'] = (bool)($additionalInformation['register_with_bolt'] ?? false);

            $loggedIn = (bool)($additionalInformation['logged_in_with_bolt'] ?? false);
            $newCard = (bool)($additionalInformation['add_new_card'] ?? false);
            $canSaveCard = $loggedIn && $newCard;

            $request = array_merge(
                $request,
                $this->preparePaymentInformation(
                    $card,
                    $billingAddress,
                    $canSaveCard,
                    $additionalInformation['billing_address_id'] ?? ''
                )
            );
        } else {
            $request['credit_card_id'] = $card['id'];
        }

        $customerToken = $this->tokenSessionStorage->retrieve();
        $authHeaders = $customerToken ? ['Authorization' => 'bearer ' . $customerToken->getAccessToken()] : [];

        return [
            'uri' => $uri,
            'method' => Http::METHOD_POST,
            'body' => $request,
            'headers' => array_merge(['Content-Type' => 'application/json'], $authHeaders),
        ];
    }

    /**
     * Returns the data of the items that are included in the shopping cart
     *
     * @param int $quoteId
     * @return array
     */
    private function getCartItems(int $quoteId): array
    {
        try {
            /** @var  \Magento\Quote\Model\Quote $quote */
            $quote = $this->quoteRepository->get($quoteId);
        } catch (NoSuchEntityException $exception) {
            $this->logger->error(
                'Could not obtain the cart items',
                ['quote' => $quoteId, 'exception' => $exception]
            );
            return [];
        }

        return array_map(
            function ($cartItem): array {
                $productType = $cartItem->getProductType();
                return [
                    'reference' => $cartItem->getSku(),
                    'name' => $cartItem->getName(),
                    'total_amount' => $this->formatAmount((float)$cartItem->getBaseRowTotalInclTax()),
                    'unit_price' => $this->formatAmount((float)$cartItem->getBasePrice()),
                    'tax_amount' => $this->formatAmount((float)$cartItem->getBaseTaxAmount()),
                    'quantity' => $cartItem->getQty(),
                    'type' => $productType ? $this->mapProductType($productType) : self::DEFAULT_PRODUCT_TYPE,
                ];
            },
            $quote->getAllVisibleItems()
        );
    }

    /**
     * Maps the product types defined in Magento with the types defined in Bolt
     *
     * @param string $itemType
     * @return string
     */
    private function mapProductType(string $itemType): string
    {
        foreach ($this->productMapping as $type => $mapping) {
            if (in_array($itemType, $mapping, true)) {
                return $type;
            }
        }
        return self::DEFAULT_PRODUCT_TYPE;
    }

    /**
     * Format amount with grouped thousands
     *
     * @param float|null $amount
     * @return int
     */
    private function formatAmount(?float $amount): int
    {
        if ($amount === null) {
            return self::EMPTY_AMOUNT;
        }

        return (int)number_format($amount, 2, '', '');
    }

    /**
     * Map address fields
     *
     * @param OrderAddressInterface|null $address
     * @return array|null
     */
    private function mapAddress(?OrderAddressInterface $address): ?array
    {
        if ($address === null) {
            return null;
        }
        $addressStreet1 = null;
        $addressStreet2 = null;
        $addressStreet3 = null;
        $addressStreet4 = null;
        if (is_array($address->getStreet())) {
            $addressStreet1 = $address->getStreet()[0];
            $addressStreet2 = $address->getStreet()[1] ?? null;
            $addressStreet3 = $address->getStreet()[2] ?? null;
            $addressStreet4 = $address->getStreet()[3] ?? null;
        }
        $country = $this->countryFactory->create()
            ->loadByCode($address->getCountryId());
        return [
            'first_name' => $address->getFirstname(),
            'last_name' => $address->getLastname(),
            'street_address1' => $addressStreet1,
            'street_address2' => $addressStreet2,
            'street_address3' => $addressStreet3,
            'street_address4' => $addressStreet4,
            'locality' => $address->getCity(),
            'region' => $address->getRegion(),
            'postal_code' => $address->getPostcode(),
            'country_code' => $address->getCountryId(),
            'country' => $country->getName(),
            'company' => $address->getCompany(),
            'phone' => $address->getTelephone(),
            'email' => $address->getEmail()
        ];
    }

    /**
     * Processing the saving of a payment method within the generation of an authorisation request
     *
     * @param array $card
     * @param OrderAddressInterface $billingAddress
     * @param bool $canSaveCard
     * @param string $addressId
     * @return array
     */
    private function preparePaymentInformation(
        array                 $card,
        OrderAddressInterface $billingAddress,
        bool                  $canSaveCard,
        string                $addressId
    ): array {
        $result = [];
        $cardData = [
            'token' => $card['token'],
            'last4' => $card['last4'],
            'bin' => $card['bin'],
            'expiration' => $card['expiration'],
            'token_type' => $card['token_type'],
        ];
        if ($addressId) {
            $cardData['billing_address_id'] = $addressId;
        } else {
            $cardData['billing_address'] = $this->mapAddress($billingAddress);
        }
        if ($canSaveCard) {
            try {
                $newCardData = $this->paymentMethodService->addPaymentMethod($cardData);
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('Failed to save the payment information, error %s', $e->getMessage()),
                    ['exception' => $e]
                );
                $this->messageManager->addWarningMessage(
                    __('An error occurred while saving the payment information in the Bolt wallet.')
                );
                $newCardData = [];
            }
            if (isset($newCardData['id'])) {
                $result['credit_card_id'] = $newCardData['id'];
            } else {
                $result['credit_card'] = $cardData;
            }
        } else {
            $result['credit_card'] = $cardData;
        }
        return $result;
    }

    /**
     * Processing the saving of an address within the generation of an authorisation request
     *
     * @param OrderInterface $order
     * @param OrderAddressInterface|null $shippingAddress
     * @param string $boltAddressId
     * @param bool $addAddress
     * @return array
     */
    private function prepareShippingAddressInformation(
        OrderInterface         $order,
        ?OrderAddressInterface $shippingAddress,
        string                 $boltAddressId,
        bool                   $addAddress
    ): array {
        $shipments = [];
        $shipment = [];

        if ($addAddress && !$boltAddressId && $shippingAddress) {
            try {
                $addressResponse = $this->addressService->addAddress($this->mapAddress($shippingAddress));
                $boltAddressId = $addressResponse['id'] ?? '';
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('Failed to save the address, error %s', $e->getMessage()),
                    ['exception' => $e]
                );
                $this->messageManager->addWarningMessage(
                    __('An error occurred while saving the address information in the Bolt wallet.')
                );
                $boltAddressId = '';
            }
        }

        if ($boltAddressId) {
            $shipment['shipping_address_id'] = $boltAddressId;
        } else {
            $shipment['shipping_address'] = $this->mapAddress($shippingAddress);
        }

        $shipment['cost'] = $this->formatAmount($order->getBaseShippingAmount());
        $shipment['tax_amount'] = $this->formatAmount($order->getBaseShippingTaxAmount());

        $shipments[] = $shipment;

        return $shipments;
    }

    /**
     * Validate credit card
     *
     * @param array $creditCard
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function validateCreditCard(array $creditCard): string
    {
        $lettersAndNumbersValidator = new Regex('/^[a-zA-Z0-9]+$/');
        $numbersValidator = new Regex('/^[0-9]+$/');
        $fourNumbersDashAndTwoNumbersValidator = new Regex('/^[0-9]{4}-[0-9]{2}$/');
        $currentYearMonth = sprintf('%s-%s', date('Y'), date('m'));

        if (!empty($creditCard['id'])) {
            if (!$this->noHtmlValidator->validate($creditCard['id'])) {
                return 'Credit card ID is missing or invalid.';
            }
        } else {
            if (empty($creditCard['token']) || !$this->noHtmlValidator->validate($creditCard['token'])) {
                return 'Credit card token is missing or invalid.';
            }
            if (empty($creditCard['last4']) || !$numbersValidator->isValid($creditCard['last4'])) {
                return 'Credit card last4 is missing or invalid.';
            }
            if (empty($creditCard['bin']) || !$numbersValidator->isValid($creditCard['bin'])) {
                return 'Credit card bin is missing or invalid.';
            }
            if (empty($creditCard['expiration'])
                || !$fourNumbersDashAndTwoNumbersValidator->isValid($creditCard['expiration'])
                || $creditCard['expiration'] < $currentYearMonth
            ) {
                return 'Credit card expiration date is missing or invalid.';
            }
            if (empty($creditCard['token_type']) || !$lettersAndNumbersValidator->isValid($creditCard['token_type'])) {
                return 'Credit card token is missing or invalid.';
            }
        }
        return '';
    }
}
