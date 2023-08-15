<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model\Bolt\Auth;

use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthToken;

/**
 * Exposes some methods to manage customer tokens stored in the session
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class OauthTokenSessionStorage
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var OauthTokenRenovator
     */
    private $renovator;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Session $customerSession
     * @param OauthTokenRenovator $renovator
     * @param Json $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Session $customerSession,
        OauthTokenRenovator $renovator,
        Json $serializer,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->renovator = $renovator;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * Returns true if there is a customer token stored in the current session
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->customerSession->getBoltCustomerToken());
    }

    /**
     * Returns the customer token stored in the current session or null otherwise
     *
     * @return OauthToken|null
     */
    public function retrieve(): ?OauthToken
    {
        $customerTokenInSession = $this->customerSession->getBoltCustomerToken();

        if (!$customerTokenInSession) {
            return null;
        }

        $content = $this->serializer->unserialize($customerTokenInSession);

        $customerToken = new OauthToken(
            $content[OauthToken::ACCESS_TOKEN_KEY],
            $content[OauthToken::ACCESS_TOKEN_SCOPE_KEY],
            $content[OauthToken::EXPIRES_AT_KEY],
            $content[OauthToken::REFRESH_TOKEN_KEY],
            $content[OauthToken::REFRESH_TOKEN_SCOPE_KEY],
            $content[OauthToken::ID_TOKEN_KEY] ?? null,
        );

        if (!$customerToken->isExpired()) {
            return $customerToken;
        }

        $refreshToken = null;

        try {
            $refreshToken = $this->renovator->refresh(
                $customerToken->getRefreshToken(),
                $customerToken->getRefreshTokenScope()
            );
            $this->store($refreshToken);
        } catch (Exception $exception) {
            $this->logger->error(
                'Could not refresh the customer token',
                ['exception' => $exception]
            );
        }

        return $refreshToken;
    }

    /**
     * Stores the given customer token in the current session
     *
     * @param OauthToken $customerToken
     * @return void
     */
    public function store(OauthToken $customerToken): void
    {
        $this->customerSession->unsBoltCustomerToken();
        $this->customerSession->setBoltCustomerToken(
            $this->serializer->serialize($customerToken->toArray())
        );
    }
}
