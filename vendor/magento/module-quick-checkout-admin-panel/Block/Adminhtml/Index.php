<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Block\Adminhtml;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\QuickCheckout\Model\Config;
use Magento\QuickCheckout\ViewModel\Metadata;
use Magento\QuickCheckoutAdminPanel\Model\Acl\ConfigSectionGuard;
use Magento\Store\Model\ScopeInterface;

/**
 * @api
 */
class Index extends Template
{
    /**
     * Config path used for frontend url
     */
    private const FRONTEND_URL_PATH = 'quick_checkout_admin_panel/frontend_url';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Metadata
     */
    private Metadata $metadata;

    /**
     * @var Session
     */
    private Session $authSession;

    /**
     * @var ConfigSectionGuard
     */
    private ConfigSectionGuard $quickCheckoutConfigGuard;

    /**
     * @param Context $context
     * @param Config $config
     * @param Metadata $metadata
     * @param Session $authSession
     * @param ConfigSectionGuard $quickCheckoutConfigGuard
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Metadata $metadata,
        Session $authSession,
        ConfigSectionGuard $quickCheckoutConfigGuard,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->metadata = $metadata;
        $this->authSession = $authSession;
        $this->quickCheckoutConfigGuard = $quickCheckoutConfigGuard;
    }

    /**
     * Returns config for frontend url
     *
     * @return string
     */
    public function getFrontendUrl(): string
    {
        return (string)$this->_scopeConfig->getValue(
            self::FRONTEND_URL_PATH,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Return a JSON map of config values
     *
     * @return string
     */
    public function getConfigJson(): string
    {
        $config = [
            'extVersion' => $this->getCurrentVersion(),
            'hasConfigAccess' => $this->hasConfigAccess(),
            'hasConfiguredKeys' => $this->config->hasConfiguredKeys(),
            'isAdminUsageEnabled' => $this->config->isAdminUsageEnabled(),
            'canTrackCheckout' => $this->config->isCheckoutTrackingEnabled(),
            'settingsURL' => $this->getUrl('adminhtml/system_config/edit/section/checkout/'),
            'customersURL' => $this->getUrl('customer/index'),
            'reportingURL' => $this->getUrl('quickcheckoutadminpanel/reporting/index/')
        ];

        return json_encode($config);
    }

    /**
     * Returns the current version of the extension
     *
     * @return string
     */
    private function getCurrentVersion(): string
    {
        try {
            $metadata = $this->metadata->getTrackingAccountData();
            return $metadata['quickCheckout_productVersion'];
        } catch (FileSystemException $error) {
            return '';
        }
    }

    /**
     * Checks if the current user has access to the quick checkout config
     *
     * @return bool
     */
    private function hasConfigAccess(): bool
    {
        $user = $this->authSession->getUser();
        if ($user === null) {
            return false;
        }
        return $this->quickCheckoutConfigGuard->isAllowed($user);
    }
}
