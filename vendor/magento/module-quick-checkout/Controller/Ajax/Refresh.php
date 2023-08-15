<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Controller\Ajax;

use Exception;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthException;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthToken;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenResolver;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenSessionStorage;
use Psr\Log\LoggerInterface;

/**
 * Bolt access token refresh
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Refresh implements HttpPostActionInterface, CsrfAwareActionInterface
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
     * @var CustomerSession
     */
    private $customerSession;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AccountManagement class constructor
     *
     * @param RequestInterface $request
     * @param OauthTokenResolver $tokenResolver
     * @param CustomerSession $customerSession
     * @param Json $serializer
     * @param JsonFactory $resultJsonFactory
     * @param OauthTokenSessionStorage $tokenSessionStorage
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        OauthTokenResolver $tokenResolver,
        CustomerSession $customerSession,
        Json $serializer,
        JsonFactory $resultJsonFactory,
        OauthTokenSessionStorage $tokenSessionStorage,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->tokenResolver = $tokenResolver;
        $this->customerSession = $customerSession;
        $this->serializer = $serializer;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->tokenSessionStorage = $tokenSessionStorage;
        $this->logger = $logger;
    }

    /**
     * Exchanges an authorization code for a new access token
     *
     * Expects a POST. ex for JSON {"code":"auth_code"}
     *
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $response = ['success' => true];

        if ($this->tokenSessionStorage->isEmpty()) {
            $response['success'] = false;
            return $resultJson->setData($response);
        }

        try {
            $payload = $this->serializer->unserialize($this->request->getContent());
            $token = $this->exchangeAccessCode($payload['code'] ?? '');
            $this->tokenSessionStorage->store($token);
        } catch (Exception $exception) {
            $this->logException($exception);
            $response = ['success' => false];
        }
        return $resultJson->setData($response);
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
     * Logs an exception that took place during the login
     *
     * @param Exception $exception
     * @return void
     */
    private function logException(Exception $exception): void
    {
        $this->logger->error(
            sprintf('Refresh token failed: %s', $exception->getMessage()),
            ['exception', $exception]
        );
    }

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request) :? InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request) :? bool
    {
        return true;
    }
}
