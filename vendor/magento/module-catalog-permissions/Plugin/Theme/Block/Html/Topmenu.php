<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Plugin\Theme\Block\Html;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Customer\Model\Session\Storage as CustomerSessionStorage;

/**
 * Plugin for \Magento\Theme\Block\Html\Topmenu
 */
class Topmenu
{
    /**
     * @var ConfigInterface
     */
    private $catalogPermissionsConfig;

    /**
     * @var CustomerSessionStorage
     */
    private $customerSessionStorage;

    /**
     * @param ConfigInterface $catalogPermissionsConfig
     * @param CustomerSessionStorage $customerSessionStorage
     */
    public function __construct(
        ConfigInterface $catalogPermissionsConfig,
        CustomerSessionStorage $customerSessionStorage
    ) {
        $this->catalogPermissionsConfig = $catalogPermissionsConfig;
        $this->customerSessionStorage = $customerSessionStorage;
    }

    /**
     * Add Customer Group identifier to cache key.
     *
     * If Catalog Permissions are enabled, we must append a Customer Group ID to the cache key so that menu block
     * caches are not shared between Customer Groups.
     *
     * @param \Magento\Theme\Block\Html\Topmenu $subject
     * @param array $result
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCacheKeyInfo(\Magento\Theme\Block\Html\Topmenu $subject, $result)
    {
        if ($this->catalogPermissionsConfig->isEnabled()) {
            $result['customer_group_id'] = $this->customerSessionStorage->getCustomerGroupId();
        }

        return $result;
    }
}
