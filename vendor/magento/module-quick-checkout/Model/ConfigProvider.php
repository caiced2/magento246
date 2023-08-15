<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Locale\ResolverInterface;
use Magento\QuickCheckout\Api\AccountRepositoryInterface;
use Magento\QuickCheckout\Model\Adminhtml\Source\Network;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenSessionStorage;

/**
 * Provide configuration for checkout components
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'quick_checkout';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var AccountRepositoryInterface
     */
    private $accountRepository;

    /**
     * @var OauthTokenSessionStorage
     */
    private OauthTokenSessionStorage $oauthTokenSessionStorage;

    /**
     * Config provider class constructor
     *
     * @param Config $config
     * @param ResolverInterface $localeResolver
     * @param CustomerSession $customerSession
     * @param AccountRepositoryInterface $accountRepository
     * @param OauthTokenSessionStorage $oauthTokenSessionStorage
     */
    public function __construct(
        Config $config,
        ResolverInterface $localeResolver,
        CustomerSession $customerSession,
        AccountRepositoryInterface $accountRepository,
        OauthTokenSessionStorage $oauthTokenSessionStorage
    ) {
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->customerSession = $customerSession;
        $this->accountRepository = $accountRepository;
        $this->oauthTokenSessionStorage = $oauthTokenSessionStorage;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        if (!$this->config->isEnabled()) {
            return [];
        }

        return [
            'payment' => [
                self::CODE => [
                    'publishableKey' => $this->config->getPublishableKey(),
                    'locale' => str_replace('_', '-', $this->localeResolver->getLocale()),
                    'canDisplayOtpPopup' => $this->config->canDisplayOtpPopup(),
                    'creditCardComponentConfig' => $this->config->getCreditCardFormConfig(),
                    'isLoggedInBolt' => $this->isLoggedInBolt(),
                    'canUseSso' => $this->customerSession->getCanUseBoltSso(),
                    'canTrackCheckout' => $this->config->isCheckoutTrackingEnabled(),
                    'canNavigateToPayment' => $this->config->isPaymentTheNextStage(),
                    'isBoltLoginAvailable' => $this->isBoltLoginAvailable(),
                    'isAutoLoginEnabled' => $this->config->isAutoLoginEnabled(),
                    'autoLoginNetwork' => $this->getAutoLoginNetwork(),
                    'hasWriteAccess' => $this->hasWriteAccess()
                ]
            ]
        ];
    }

    /**
     * Checks if the user is logged in Bolt's network
     *
     * @return bool
     */
    private function isLoggedInBolt(): bool
    {
        return !empty($this->customerSession->getBoltCustomerToken());
    }

    /**
     * Checks if the user is logged in Bolt's network
     *
     * @return bool
     */
    private function hasWriteAccess(): bool
    {
        $token = $this->oauthTokenSessionStorage->retrieve();
        if ($token) {
            return $token->canManageAccountDetails();
        }
        return false;
    }

    /**
     * Checks if Bolt's login is available
     *
     * @return bool
     */
    private function isBoltLoginAvailable(): bool
    {
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }

        if ($this->isLoggedInBolt()) {
            return false;
        }

        $customer = $this->customerSession->getCustomer();

        return $this->accountRepository->hasAccount($customer->getEmail());
    }

    /**
     * Get the name of the selected network
     *
     * @return string
     */
    private function getAutoLoginNetwork(): string
    {
        $autoLoginNetwork = $this->config->getAutoLoginNetwork();
        return $autoLoginNetwork !== '' ? $autoLoginNetwork : Network::BOLT;
    }
}
