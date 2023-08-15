<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Plugin\Checkout;

use Magento\Checkout\Model\CompositeConfigProvider as CheckoutCompositeConfigProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\QuickCheckout\Model\Bolt\Auth\OauthTokenSessionStorage;
use Magento\QuickCheckout\Model\Config;

/**
 * Unset customer addresses to not render them in default address list
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CompositeConfigProvider
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var OauthTokenSessionStorage
     */
    private $oauthTokenSessionStorage;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param CustomerSession $customerSession
     * @param OauthTokenSessionStorage $oauthTokenSessionStorage
     * @param Config $config
     */
    public function __construct(
        CustomerSession $customerSession,
        OauthTokenSessionStorage $oauthTokenSessionStorage,
        Config $config
    ) {
        $this->customerSession = $customerSession;
        $this->oauthTokenSessionStorage = $oauthTokenSessionStorage;
        $this->config = $config;
    }

    /**
     * Unset customer addresses
     *
     * @param CheckoutCompositeConfigProvider $subject
     * @param array $config
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetConfig(CheckoutCompositeConfigProvider $subject, $config)
    {
        if (!$this->config->isEnabled()
            || !$this->customerSession->isLoggedIn()
            || !$this->customerSession->getCanUseBoltSso()
            || $this->oauthTokenSessionStorage->isEmpty()
        ) {
            return $config;
        }
        if (isset($config['customerData']['addresses'])) {
            $config['customerData']['addresses'] = [];
        }
        return $config;
    }
}
