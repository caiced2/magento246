<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Controller\Ajax;

use Exception;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\QuickCheckout\Model\Adminhtml\Source\Network;
use Magento\QuickCheckout\Model\Bolt\Auth\IdTokenDecoder;
use Magento\QuickCheckout\Model\Bolt\Auth\IdTokenPayload;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthException;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthToken;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenResolver;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenSessionStorage;
use Magento\QuickCheckout\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Magento and Bolt account login
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class Login implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OauthTokenResolver
     */
    private $tokenResolver;

    /**
     * @var IdTokenDecoder
     */
    private $tokenDecoder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var Customer
     */
    private $customerResource;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var Json $serializer
     */
    private $serializer;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var OauthTokenSessionStorage
     */
    private $tokenSessionStorage;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AccountManagement class constructor
     *
     * @param RequestInterface $request
     * @param IdTokenDecoder $tokenDecoder
     * @param OauthTokenResolver $tokenResolver
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param Customer $customerResource
     * @param CustomerSession $customerSession
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Json $serializer
     * @param JsonFactory $resultJsonFactory
     * @param OauthTokenSessionStorage $tokenSessionStorage
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface         $request,
        IdTokenDecoder           $tokenDecoder,
        OauthTokenResolver       $tokenResolver,
        StoreManagerInterface    $storeManager,
        CustomerFactory          $customerFactory,
        Customer                 $customerResource,
        CustomerSession          $customerSession,
        CookieManagerInterface   $cookieManager,
        CookieMetadataFactory    $cookieMetadataFactory,
        Json                     $serializer,
        JsonFactory              $resultJsonFactory,
        OauthTokenSessionStorage $tokenSessionStorage,
        Config                   $config,
        LoggerInterface          $logger
    ) {
        $this->request = $request;
        $this->tokenDecoder = $tokenDecoder;
        $this->tokenResolver = $tokenResolver;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResource;
        $this->customerSession = $customerSession;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->serializer = $serializer;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->tokenSessionStorage = $tokenSessionStorage;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Login registered users and initiate a session.
     *
     * Expects a POST. ex for JSON {"code":"auth_code"}
     *
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $response = [
            'success' => true,
            'message' => __('Login successful.'),
            'isLoggedInBothNetworks' => false,
        ];

        try {
            $payload = (array)$this->serializer->unserialize($this->request->getContent());
            $accessCode = $payload['code'] ?? '';
            $isAutoLogin = !empty($payload['isAutoLogin']);
            $autoLoginNetwork = $this->config->getAutoLoginNetwork();
            $isLoggedInMerchantNetwork = false;
            $token = $this->exchangeAccessCode($accessCode);
            $payload = $this->decode($token);
            if ($this->shouldLoginMerchantNetwork($isAutoLogin, $autoLoginNetwork)) {
                $isLoggedInMerchantNetwork = $this->loginMerchantNetwork($payload);
            }
            $this->loginBoltNetwork($token);
            $this->customerSession->setCanUseBoltSso($isLoggedInMerchantNetwork);
            $response['isLoggedInBothNetworks'] = $isLoggedInMerchantNetwork;
        } catch (Exception $exception) {
            $this->logException($exception);
            $response['success'] = false;
            $response['message'] = __('Could not authenticate. Please try again later.');
        }

        $response['hasWriteAccess'] = isset($token) && $token->canManageAccountDetails();
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($response);
    }

    /**
     * Start's a new session in Bolt's network with the given access token
     *
     * @param OauthToken $token
     * @return void
     */
    public function loginBoltNetwork(OauthToken $token): void
    {
        $this->tokenSessionStorage->store($token);
    }

    /**
     * Exchanges an access code for an oauth token
     *
     * @param string $code
     * @return OauthToken
     * @throws OauthException
     * @throws ClientException
     * @throws ConverterException
     */
    private function exchangeAccessCode(string $code): OauthToken
    {
        return $this->tokenResolver->exchange($code);
    }

    /**
     * Decodes the oauth token to extract the associated payload
     *
     * @param OauthToken $oauthToken
     * @return IdTokenPayload
     * @throws ClientException
     * @throws ConverterException
     * @throws OauthException
     */
    private function decode(OauthToken $oauthToken): IdTokenPayload
    {
        return $this->tokenDecoder->decode($oauthToken->getIdToken());
    }

    /**
     * Identify customer using the email address and start a session
     *
     * @param IdTokenPayload $payload
     * @return bool
     * @throws FailureToSendException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function loginMerchantNetwork(IdTokenPayload $payload): bool
    {
        if (!$payload->isEmailVerified()) {
            return false;
        }

        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customer = $this->customerFactory->create();
        $customer = $customer->setWebsiteId($websiteId);
        $this->customerResource->loadByEmail($customer, $payload->getEmail());

        if (!$customer->getId()) {
            return false;
        }

        $this->customerSession->setCustomerAsLoggedIn($customer);
        $this->triggerLocalCacheStorageCleanup();
        return true;
    }

    /**
     * Triggers the cleanup of local cache storage by setting a http cookie
     *
     * @return void
     * @throws InputException
     * @throws FailureToSendException
     */
    private function triggerLocalCacheStorageCleanup(): void
    {
        if ($this->cookieManager->getCookie('mage-cache-sessid')) {
            $metadata = $this->cookieMetadataFactory->createCookieMetadata();
            $metadata->setPath('/');
            $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
        }
    }

    /**
     * Logs an exception that took place during the login
     *
     * @param Exception $exception
     * @return void
     */
    private function logException(Exception $exception): void
    {
        $this->logger->error(
            sprintf('Login failed: %s', $exception->getMessage()),
            ['exception', $exception]
        );
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Checks if the shopper must be logged in Merchant's network
     *
     * @param bool $isAutoLogin
     * @param string $autoLoginNetwork
     * @return bool
     */
    public function shouldLoginMerchantNetwork(bool $isAutoLogin, string $autoLoginNetwork): bool
    {
        return !$isAutoLogin || $autoLoginNetwork === Network::BOLT_PLUS_MERCHANT;
    }
}
