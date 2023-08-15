<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Helper;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;

/**
 * Base helper
 * @api
 * @since 100.0.2
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Core store config
     *
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param ConfigInterface $config
     */
    public function __construct(Context $context, Session $customerSession, ConfigInterface $config)
    {
        $this->customerSession = $customerSession;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Retrieve config value for category access permission
     *
     * @param int $storeId
     * @param int $customerGroupId
     * @return bool
     */
    public function isAllowedCategoryView($storeId = null, $customerGroupId = null)
    {
        return $this->isAllowedGrant(
            $this->config->getCatalogCategoryViewMode($storeId),
            $this->config->getCatalogCategoryViewGroups($storeId),
            $customerGroupId
        );
    }

    /**
     * Retrieve config value for product price permission
     *
     * @param int $storeId
     * @param int $customerGroupId
     * @return bool
     */
    public function isAllowedProductPrice($storeId = null, $customerGroupId = null)
    {
        return $this->isAllowedGrant(
            $this->config->getCatalogProductPriceMode($storeId),
            $this->config->getCatalogProductPriceGroups($storeId),
            $customerGroupId
        );
    }

    /**
     * Retrieve config value for checkout items permission
     *
     * @param int $storeId
     * @param int $customerGroupId
     * @return bool
     */
    public function isAllowedCheckoutItems($storeId = null, $customerGroupId = null)
    {
        return $this->isAllowedGrant(
            $this->config->getCheckoutItemsMode($storeId),
            $this->config->getCheckoutItemsGroups($storeId),
            $customerGroupId
        );
    }

    /**
     * Retrieve config value for catalog search availability
     *
     * @return bool
     */
    public function isAllowedCatalogSearch()
    {
        $groups = $this->config->getCatalogSearchDenyGroups();

        if (!$groups) {
            return true;
        }

        return !in_array($this->customerSession->getCustomerGroupId(), $groups);
    }

    /**
     * Retrieve landing page url
     *
     * @return string
     */
    public function getLandingPageUrl()
    {
        return $this->_getUrl('', ['_direct' => $this->config->getRestrictedLandingPage()]);
    }

    /**
     * Retrieve is allowed grant from configuration
     *
     * @param string $mode
     * @param string[] $groups
     * @param int|null $customerGroupId
     * @return bool
     */
    protected function isAllowedGrant($mode, $groups, $customerGroupId = null)
    {
        if ($mode == ConfigInterface::GRANT_CUSTOMER_GROUP) {
            if (!$groups) {
                return false;
            }

            if ($customerGroupId === null) {
                $customerGroupId = $this->customerSession->getCustomerGroupId();
            }

            return in_array($customerGroupId, $groups);
        }

        return $mode == ConfigInterface::GRANT_ALL;
    }
}
