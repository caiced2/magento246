<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\QuickCheckout\Api\StorefrontAccountRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Storefront account repository allows to check if email exists in Magento
 */
class StorefrontAccountRepository implements StorefrontAccountRepositoryInterface
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AccountManagementInterface
     */
    private $customerAccountManagement;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Request $request
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $customerAccountManagement
     * @param Config $config
     */
    public function __construct(
        Request $request,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $customerAccountManagement,
        Config $config
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->config = $config;
    }

    /**
     * Check if email exists in Storefront
     *
     * @param string $email
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function hasAccount(string $email): bool
    {
        $this->extensionIsEnabled();

        $payload = $this->request->getContent();
        $token = (string) $this->request->getHeader('X-Bolt-Hmac-Sha256');

        if ($this->isEmailAvailable($email) || !$this->verifySignature($payload, $token)) {
            $this->accountNotFound();
        }

        return true;
    }

    /**
     * It verifies HMAC signature
     *
     * @param string $payload
     * @param string $token
     * @return bool
     */
    private function verifySignature(string $payload, string $token): bool
    {
        $signingSecret = $this->config->getSigningSecret();
        $computedToken = base64_encode(hash_hmac('sha256', $payload, $signingSecret, true));

        return ($computedToken === $token);
    }

    /**
     * Check if email is available
     *
     * @param string $email
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function isEmailAvailable(string $email): bool
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        return $this->customerAccountManagement->isEmailAvailable($email, $websiteId);
    }

    /**
     * Throw an exception if the extension is not enabled.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    private function extensionIsEnabled(): void
    {
        if (!$this->config->isEnabled()) {
            $this->accountNotFound();
        }
    }

    /**
     * Throw exception
     *
     * @return void
     * @throws NoSuchEntityException
     */
    private function accountNotFound(): void
    {
        // Used NoSuchEntityException bacause it sets the HTTP status code 404.
        throw new NoSuchEntityException();
    }
}
