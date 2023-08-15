<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model;

use Laminas\Validator\Regex;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Country as CountryResource;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\QuickCheckout\Api\AccountRepositoryInterface;
use Magento\QuickCheckout\Api\Data\AccountInterface;
use Magento\QuickCheckout\Api\Data\AccountInterfaceFactory;
use Magento\QuickCheckout\Api\Data\AddressInterface;
use Magento\QuickCheckout\Api\Data\AddressInterfaceFactory;
use Magento\QuickCheckout\Gateway\Http\TransferFactory;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenSessionStorage;
use Psr\Log\LoggerInterface;

/**
 * Account repository allows to check if email exists in Bolt and retrieve account information
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AccountRepository implements AccountRepositoryInterface
{
    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var ClientInterface
     */
    private $serviceClient;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var AccountInterfaceFactory
     */
    private $accountDataFactory;

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var AddressValidatorInterface
     */
    private $shippingAddressValidator;

    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var CountryResource
     */
    private $countryResource;

    /**
     * @var NoHtmlValidator
     */
    private $noHtmlValidator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var AddressValidatorInterface
     */
    private $billingAddressValidator;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var OauthTokenSessionStorage
     */
    private $tokenSessionStorage;

    /**
     * @param TransferFactory $transferFactory
     * @param ClientInterface $serviceClient
     * @param DataObjectHelper $dataObjectHelper
     * @param AccountInterfaceFactory $accountDataFactory
     * @param RegionFactory $regionFactory
     * @param CustomerSession $customerSession
     * @param AddressValidatorInterface $shippingAddressValidator
     * @param AddressValidatorInterface $billingAddressValidator
     * @param DirectoryHelper $directoryHelper
     * @param CountryFactory $countryFactory
     * @param CountryResource $countryResource
     * @param NoHtmlValidator $noHtmlValidator
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param OauthTokenSessionStorage $tokenSessionStorage
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TransferFactory $transferFactory,
        ClientInterface $serviceClient,
        DataObjectHelper $dataObjectHelper,
        AccountInterfaceFactory $accountDataFactory,
        RegionFactory $regionFactory,
        CustomerSession $customerSession,
        AddressValidatorInterface $shippingAddressValidator,
        AddressValidatorInterface $billingAddressValidator,
        DirectoryHelper $directoryHelper,
        CountryFactory $countryFactory,
        CountryResource $countryResource,
        NoHtmlValidator $noHtmlValidator,
        LoggerInterface $logger,
        DateTime $dateTime,
        CustomerRepositoryInterface $customerRepository,
        AddressInterfaceFactory $addressFactory,
        OauthTokenSessionStorage $tokenSessionStorage
    ) {
        $this->transferFactory = $transferFactory;
        $this->serviceClient = $serviceClient;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->accountDataFactory = $accountDataFactory;
        $this->regionFactory = $regionFactory;
        $this->customerSession = $customerSession;
        $this->shippingAddressValidator = $shippingAddressValidator;
        $this->billingAddressValidator = $billingAddressValidator;
        $this->directoryHelper = $directoryHelper;
        $this->countryFactory = $countryFactory;
        $this->countryResource = $countryResource;
        $this->noHtmlValidator = $noHtmlValidator;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->customerRepository = $customerRepository;
        $this->addressFactory = $addressFactory;
        $this->tokenSessionStorage = $tokenSessionStorage;
    }

    /**
     * Check if email exists in Bolt
     *
     * @param string $email
     * @return bool
     * @throws LocalizedException
     */
    public function hasAccount(string $email): bool
    {
        if (!$this->validateEmail($email)) {
            throw new LocalizedException(__('Invalid email address.'));
        }
        $result = false;
        if (!empty($email)) {
            $request = [
                'uri' => '/v1/account/exists?email=' . rawurlencode($email),
                'method' => Http::METHOD_GET,
                'body' => '',
                'headers' => []
            ];
            $transferObject = $this->transferFactory->create($request);
            try {
                $result = (bool)($this->serviceClient->placeRequest($transferObject)['has_bolt_account'] ?? false);
            } catch (\Exception $e) {
                $this->throwException($e);
            }
        }
        return $result;
    }

    /**
     * Throw an exception
     *
     * @param \Exception $e
     * @throws LocalizedException
     */
    private function throwException(\Exception $e)
    {
        $this->logger->error(
            sprintf('Error %s', $e->getMessage()),
            ['exception' => $e]
        );
        throw new LocalizedException(__('An error happened when processing the request. Try again later.'));
    }

    /**
     * Retrieve account information
     *
     * @return AccountInterface
     * @throws LocalizedException
     */
    public function getAccountDetails(): AccountInterface
    {
        $result = [];

        $customerToken = $this->tokenSessionStorage->retrieve();

        if ($customerToken) {
            $request = [
                'uri' => '/v1/account',
                'method' => Http::METHOD_GET,
                'body' => '',
                'headers' => [
                    'Authorization' => 'bearer ' . $customerToken->getAccessToken(),
                ]
            ];
            try {
                $transferObject = $this->transferFactory->create($request);
                $response = $this->serviceClient->placeRequest($transferObject);
                $error = $this->validateAccount($response);
                if ($error) {
                    throw new \InvalidArgumentException($error);
                }
                $response = $this->sanitizeAccountData($response);
                $result = $this->mapAccountData($response);
                if ($this->customerSession->isLoggedIn()) {
                    $result = $this->addCustomerAddresses($result);
                }
            } catch (\Exception $e) {
                $this->throwException($e);
            }
        }
        return $result;
    }

    /**
     * Validate email address
     *
     * @param string $email
     * @return bool
     */
    private function validateEmail(string $email): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        return false;
    }

    /**
     * Sanitize bolt api response to handle it in checkout
     *
     * @param array $response
     * @return AccountInterface
     */
    private function mapAccountData(array $response): AccountInterface
    {
        $profileEmail = $response['profile']['email'];
        $profilePhone = $response['profile']['phone'] ?? '';
        foreach ($response['addresses'] as $key => $shippingAddress) {
            $response['addresses'][$key] = $this->mapAddressRegionData($shippingAddress);
            $response['addresses'][$key] = $this->mapAddressDataValues($response['addresses'][$key]);
            $response['addresses'][$key][AddressInterface::STREET] = $this->combineStreetInfo($shippingAddress);
            $response['addresses'][$key][AddressInterface::TELEPHONE] = $profilePhone;
            $response['addresses'][$key][AddressInterface::EMAIL] = $profileEmail;
            $response['addresses'][$key][AddressInterface::EXTERNAL_ADDRESS] = true;
        }

        foreach ($response['payment_methods'] as $key => $paymentMethod) {
            if (isset($paymentMethod['billing_address'])) {
                $address = $this->mapAddressRegionData($paymentMethod['billing_address']);
                $address = $this->mapAddressDataValues($address);
                $address[AddressInterface::STREET] = $this->combineStreetInfo($address);
                $address[AddressInterface::TELEPHONE] = $profilePhone;
                $address[AddressInterface::EMAIL] = $address['email_address'] ?? $profileEmail;
                $address[AddressInterface::EXTERNAL_ADDRESS] = true;
                $response['payment_methods'][$key]['billing_address']['data'] = $address;
                $response['payment_methods'][$key]['expiration_month']
                    = $response['payment_methods'][$key]['exp_month'];
                unset($response['payment_methods'][$key]['exp_month']);
                $response['payment_methods'][$key]['expiration_year']
                    = $response['payment_methods'][$key]['exp_year'];
                unset($response['payment_methods'][$key]['exp_year']);
            }
        }

        $response['email'] = $profileEmail;
        $response['first_name'] = $response['profile']['first_name'];
        $response['last_name'] = $response['profile']['last_name'];

        $response['addresses'] = $this->sortItemsByDefaultFlag($response['addresses']);
        $response['payment_methods'] = $this->sortItemsByDefaultFlag($response['payment_methods']);

        $accountInformation = $this->accountDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $accountInformation,
            $response,
            AccountInterface::class
        );

        $addresses = [];
        foreach ($accountInformation->getAddresses() as $address) {
            if ($this->shippingAddressValidator->validate($address)) {
                $addresses[] = $address;
            }
        }

        $defaultAddressChanged = false;
        if (count($accountInformation->getAddresses()) > 0 !== count($addresses) > 0) {
            $defaultAddressChanged = true;
        }
        if (count($addresses) > 0) {
            $defaultAddressChanged = $accountInformation->getAddresses()[0]->getId() !== $addresses[0]->getId();
        }

        $accountInformation->setDefaultAddressChanged($defaultAddressChanged);
        $accountInformation->setAddresses($addresses);

        $cards = [];
        foreach ($accountInformation->getPaymentMethods() as $card) {
            if ($this->billingAddressValidator->validate($card->getBillingAddress())) {
                $cards[] = $card;
            }
        }
        $accountInformation->setPaymentMethods($cards);

        return $accountInformation;
    }

    /**
     * Add customer address to account information
     *
     * @param AccountInterface $accountInformation
     * @return AccountInterface
     */
    private function addCustomerAddresses(AccountInterface $accountInformation): AccountInterface
    {
        $addresses = $accountInformation->getAddresses();
        try {
            $customer = $this->customerRepository->get($accountInformation->getEmail());
        } catch (\Exception $e) {
            return $accountInformation;
        }
        foreach ($customer->getAddresses() as $customerAddress) {
            $address = $this->addressFactory->create();
            $customerAddressData = $customerAddress->__toArray();
            $customerAddressData['region'] = $customerAddressData['region']['region'];
            $this->dataObjectHelper->populateWithArray(
                $address,
                $customerAddressData,
                AddressInterface::class
            );
            $addresses[] = $address;
        }
        $accountInformation->setAddresses($addresses);
        return $accountInformation;
    }

    /**
     * Validate account
     *
     * @param array $account
     * @return string
     */
    private function validateAccount(array $account): string
    {
        if (empty($account['profile'])) {
            return 'Invalid response.';
        }
        if (empty($account['profile']['email']) || !$this->validateEmail($account['profile']['email'])) {
            return 'Email address is missing or invalid.';
        }
        if (empty($account['profile']['phone']) || !$this->noHtmlValidator->validate($account['profile']['phone'])) {
            return 'Telephone is missing or invalid.';
        }
        if (!isset($account['addresses'])) {
            return 'Addresses are missing.';
        }
        if (!isset($account['payment_methods'])) {
            return 'Payment information is missing.';
        }
        return '';
    }

    /**
     * Sanitize account data
     *
     * @param array $accountData
     * @return array
     */
    private function sanitizeAccountData(array $accountData): array
    {
        $sanitizedAccountData = $accountData;
        foreach ($sanitizedAccountData['addresses'] as $key => $address) {
            $error = $this->validateAddress($address);
            if ($error) {
                $this->logger->error(sprintf('Error %s', $error));
                unset($sanitizedAccountData['addresses'][$key]);
            }
        }
        foreach ($sanitizedAccountData['payment_methods'] as $key => $paymentMethod) {
            $error = $this->validateCreditCard($paymentMethod);
            if ($error) {
                $this->logger->error(sprintf('Error %s', $error));
                unset($sanitizedAccountData['payment_methods'][$key]);
            }
        }
        return $sanitizedAccountData;
    }

    /**
     * Validate address
     *
     * @param array $address
     * @return string
     */
    private function validateAddress(array $address): string
    {
        $lettersValidator = new Regex('/^[a-zA-Z]+$/');
        if (empty($address['id']) || !$this->noHtmlValidator->validate($address['id'])) {
            return 'Address ID is missing or invalid.';
        }
        if (empty($address['first_name']) || !$this->noHtmlValidator->validate($address['first_name'])) {
            return 'First name is missing or invalid.';
        }
        if (empty($address['last_name']) || !$this->noHtmlValidator->validate($address['last_name'])) {
            return 'Last name is missing or invalid.';
        }
        if (empty($address['street_address1']) || !$this->noHtmlValidator->validate($address['street_address1'])) {
            return 'Street address is missing or invalid.';
        }
        if (empty($address['locality']) || !$this->noHtmlValidator->validate($address['locality'])) {
            return 'City is missing or invalid.';
        }
        $country = $this->countryFactory->create();
        $this->countryResource->load($country, $address['country_code']);
        if (empty($address['country_code']) || !$country->getId()) {
            return 'Country ID is missing or invalid.';
        }
        if ($this->directoryHelper->isRegionRequired($address['country_code'])
            && $lettersValidator->isValid($address['country_code'])
            && (empty($address['region']) || !$this->noHtmlValidator->validate($address['region']))
        ) {
            return 'Region is missing or invalid.';
        }
        if (!in_array($address['country_code'], $this->directoryHelper->getCountriesWithOptionalZip())
            && (empty($address['postal_code']) || !$this->noHtmlValidator->validate($address['postal_code']))
        ) {
            return 'Postal code is missing or invalid.';
        }
        return '';
    }

    /**
     * Validate credit card
     *
     * @param array $creditCard
     * @return string
     */
    private function validateCreditCard(array $creditCard): string
    {
        $lettersAndNumbersValidator = new Regex('/^[a-zA-Z0-9]+$/');
        $twoDigitNumberValidator = new Regex('/^[0-9]{1,2}$/');
        $fourDigitNumberValidator = new Regex('/^[0-9]{4}$/');
        if (empty($creditCard['id']) || !$this->noHtmlValidator->validate($creditCard['id'])) {
            return 'Credit card ID is missing or invalid.';
        }
        if (empty($creditCard['network']) || !$lettersAndNumbersValidator->isValid($creditCard['network'])) {
            return 'Credit card network is missing or invalid.';
        }
        if (empty($creditCard['exp_month']) || !$twoDigitNumberValidator->isValid($creditCard['exp_month'])) {
            return 'Credit card expiration month is missing or invalid.';
        }
        if (empty($creditCard['exp_year']) || !$fourDigitNumberValidator->isValid($creditCard['exp_year'])) {
            return 'Credit card expiration year is missing or invalid.';
        }
        $currentDate = $this->dateTime->date('Y-m');
        $expirationDate = $this->dateTime->date('Y-m', $creditCard['exp_year'] . '-' . $creditCard['exp_month']);
        if ($expirationDate < $currentDate) {
            return 'Credit card has expired.';
        }
        if (empty($creditCard['billing_address']) || $this->validateAddress($creditCard['billing_address'])) {
            return 'Credit card billing address is missing or invalid.';
        }
        return '';
    }

    /**
     * Map Bolt region to Magento region
     *
     * @param array $address
     * @return array
     */
    private function mapAddressRegionData(array $address): array
    {
        $region = $this->regionFactory->create();
        $region->loadByName($address['region'], $address['country_code']);
        $address[AddressInterface::REGION_ID] = empty($region->getId()) ? null : (int)$region->getId();
        $address[AddressInterface::REGION_CODE] = empty($region->getCode()) ? null : $region->getCode();
        return $address;
    }

    /**
     * Map Bolt address data to Magento address data
     *
     * @param array $address
     * @return array
     */
    private function mapAddressDataValues(array $address): array
    {
        $address[AddressInterface::FIRSTNAME] = $address['first_name'];
        $address[AddressInterface::LASTNAME] = $address['last_name'];
        $address[AddressInterface::COUNTRY_ID] = $address['country_code'];
        $address[AddressInterface::CITY] = $address['locality'];
        $address[AddressInterface::POSTCODE] = $address['postal_code'];
        return $address;
    }

    /**
     * Build up street array to use this in checkout
     *
     * @param array $address
     * @return array|string[]
     */
    private function combineStreetInfo(array $address): array
    {
        $street = [];
        $street[] = $address['street_address1'];
        if (isset($address['street_address2']) && !empty($address['street_address2'])) {
            $street[] = $address['street_address2'];
        }
        if (isset($address['street_address3']) && !empty($address['street_address3'])) {
            $street[] = $address['street_address3'];
        }
        if (isset($address['street_address4']) && !empty($address['street_address4'])) {
            $street[] = $address['street_address4'];
        }
        return $street;
    }

    /**
     * Sort payment or shipping addresses by 'default' value
     *
     * @param array $data
     * @return array
     */
    private function sortItemsByDefaultFlag(array $data): array
    {
        $sorting = [];
        foreach ($data as $key => $row) {
            $sorting[$key] = $row['default'];
        }
        array_multisort($sorting, SORT_DESC, $data);
        return $data;
    }
}
