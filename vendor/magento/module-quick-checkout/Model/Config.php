<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Payment method config
 */
class Config
{
    public const ENVIRONMENT_SANDBOX = 'sandbox';
    public const ENVIRONMENT_PRODUCTION = 'production';

    public const STAGE_PAYMENT = 'payment';
    public const STAGE_SHIPPING = 'shipping';

    private const SDK_HOST_SANDBOX = 'connect-sandbox.bolt.com';
    private const SDK_HOST_PRODUCTION = 'connect.bolt.com';

    private const API_HOST_SANDBOX = 'api-sandbox.bolt.com';
    private const API_HOST_PRODUCTION = 'api.bolt.com';

    private const CONFIG_PATH_CARRIERS_INSTORE_ACTIVE = 'carriers/instore/active';
    private const CONFIG_PATH_ADMIN_USAGE_ENABLED = 'admin/usage/enabled';
    private const CONFIG_PATH_ACTIVE = 'payment/quick_checkout/active';
    private const CONFIG_PATH_API_KEY = 'payment/quick_checkout/api_key';
    private const CONFIG_PATH_PUBLISHABLE_KEY = 'payment/quick_checkout/publishable_key';
    private const CONFIG_PATH_SIGNING_SECRET = 'payment/quick_checkout/signing_secret';
    private const CONFIG_PATH_METHOD = 'payment/quick_checkout/method';
    private const CONFIG_PATH_CHECKOUT_TRACKING = 'payment/quick_checkout/checkout_tracking';
    private const CONFIG_PATH_NEXT_STAGE_AFTER_LOGIN = 'payment/quick_checkout/next_stage_after_login';
    private const CONFIG_PATH_AUTO_LOGIN_ENABLED = 'payment/quick_checkout/auto_login_enabled';
    private const CONFIG_PATH_AUTO_LOGIN_NETWORK = 'payment/quick_checkout/auto_login_network';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /** @var Manager */
    private $moduleManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $creditCardComponentConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Manager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param array $creditCardComponentConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Manager $moduleManager,
        StoreManagerInterface $storeManager,
        array $creditCardComponentConfig = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->moduleManager = $moduleManager;
        $this->storeManager = $storeManager;
        $this->creditCardComponentConfig = $creditCardComponentConfig;
    }

    /**
     * Is enabled?
     *
     * @param string|null $code
     * @return bool
     */
    public function isEnabled(string $code = null): bool
    {
        $path = self::CONFIG_PATH_ACTIVE;
        if ($code == null) {
            return (bool)$this->getConfigValue($path);
        }
        return (bool)$this->getConfigValue($path, ScopeInterface::SCOPE_WEBSITE, $code);
    }

    /**
     * Can display OTP popup?
     *
     * @return bool
     */
    public function canDisplayOtpPopup(): bool
    {
        return !$this->getConfigValue(self::CONFIG_PATH_CARRIERS_INSTORE_ACTIVE) && !empty($this->getPublishableKey());
    }

    /**
     * Get SDK URL
     *
     * @return string
     */
    public function getSdkUrl(): string
    {
        return 'https://' . $this->getSdkHost() . '/embed.js';
    }

    /**
     * Get host
     *
     * @return string
     */
    private function getSdkHost(): string
    {
        return $this->isProductionEnvironment() ? self::SDK_HOST_PRODUCTION : self::SDK_HOST_SANDBOX;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function getApiHost(): string
    {
        return $this->isProductionEnvironment() ? self::API_HOST_PRODUCTION : self::API_HOST_SANDBOX;
    }

    /**
     * Get bolt api key
     *
     * @param string|null $code
     * @return string|null
     */
    public function getApiKey(string $code = null): ?string
    {
        $path = self::CONFIG_PATH_API_KEY;
        if ($code == null) {
            return $this->getConfigValue($path);
        }
        return $this->getConfigValue($path, ScopeInterface::SCOPE_WEBSITE, $code);
    }

    /**
     * Get bolt signing secret
     *
     * @param string|null $code
     * @return string|null
     */
    public function getSigningSecret(string $code = null): ?string
    {
        $path = self::CONFIG_PATH_SIGNING_SECRET;
        if ($code == null) {
            return $this->getConfigValue($path);
        }
        return $this->getConfigValue($path, ScopeInterface::SCOPE_WEBSITE, $code);
    }

    /**
     * Get bolt publishable key
     *
     * @param string|null $code
     * @return string|null
     */
    public function getPublishableKey(string $code = null): ?string
    {
        $path = self::CONFIG_PATH_PUBLISHABLE_KEY;
        if ($code == null) {
            return $this->getConfigValue($path);
        }
        return $this->getConfigValue($path, ScopeInterface::SCOPE_WEBSITE, $code);
    }

    /**
     * Get configs for credit card form
     *
     * @return array
     */
    public function getCreditCardFormConfig(): array
    {
        return $this->creditCardComponentConfig;
    }

    /**
     * Get config value
     *
     * @param string $path
     * @param string $scope
     * @param string|null $code
     * @return string|null
     */
    private function getConfigValue(
        string $path,
        string $scope = ScopeInterface::SCOPE_STORE,
        string $code = null
    ): ?string {
        return $this->scopeConfig->getValue(
            $path,
            $scope,
            $code
        );
    }

    /**
     * Is production environment?
     *
     * @param string|null $code
     * @return bool
     */
    public function isProductionEnvironment(string $code = null): bool
    {
        $path = self::CONFIG_PATH_METHOD;
        if ($code == null) {
            return $this->getConfigValue($path) == self::ENVIRONMENT_PRODUCTION;
        } else {
            return $this->getConfigValue($path, ScopeInterface::SCOPE_WEBSITE, $code) == self::ENVIRONMENT_PRODUCTION;
        }
    }

    /**
     * Returns true if the admin usage tracking is enabled
     *
     * @return bool
     */
    public function isAdminUsageEnabled(): bool
    {
        $path = self::CONFIG_PATH_ADMIN_USAGE_ENABLED;
        if (!$this->moduleManager->isEnabled('Magento_AdminAnalytics')) {
            return false;
        }
        return (bool)$this->getConfigValue($path) === true;
    }

    /**
     * Returns true if the extension is active in any of the available stores
     *
     * @return bool
     */
    public function hasConfiguredKeys(): bool
    {
        foreach ($this->storeManager->getWebsites() as $website) {
            if ($this->isEnabled($website->getCode())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return true if the checkout tracking is enabled
     *
     * @param string|null $code
     * @return bool
     */
    public function isCheckoutTrackingEnabled(string $code = null): bool
    {
        $path = self::CONFIG_PATH_CHECKOUT_TRACKING;
        if ($code == null) {
            return (bool)$this->getConfigValue($path);
        }
        return (bool)$this->getConfigValue($path, ScopeInterface::SCOPE_WEBSITE, $code);
    }

    /**
     * Return true if the system has to load the Payment page as next stage
     *
     * @param string|null $code
     * @return bool
     */
    public function isPaymentTheNextStage(string $code = null): bool
    {
        $path = self::CONFIG_PATH_NEXT_STAGE_AFTER_LOGIN;
        if ($code == null) {
            return $this->getConfigValue($path) == self::STAGE_PAYMENT;
        }
        return $this->getConfigValue($path, ScopeInterface::SCOPE_WEBSITE, $code) == self::STAGE_PAYMENT;
    }

    /**
     * Returns true if automatic login is enabled
     *
     * @param string|null $code
     * @return bool
     */
    public function isAutoLoginEnabled(string $code = null): bool
    {
        $path = self::CONFIG_PATH_AUTO_LOGIN_ENABLED;
        if ($code == null) {
            return (bool)$this->getConfigValue($path);
        }
        return (bool)$this->getConfigValue($path, ScopeInterface::SCOPE_WEBSITE, $code);
    }

    /**
     * Returns the name of the selected network for the auto login
     *
     * @param string|null $code
     * @return string
     */
    public function getAutoLoginNetwork(string $code = null): string
    {
        $path = self::CONFIG_PATH_AUTO_LOGIN_NETWORK;
        if ($code == null) {
            return (string)$this->getConfigValue($path);
        }
        return (string)$this->getConfigValue($path, ScopeInterface::SCOPE_WEBSITE, $code);
    }
}
