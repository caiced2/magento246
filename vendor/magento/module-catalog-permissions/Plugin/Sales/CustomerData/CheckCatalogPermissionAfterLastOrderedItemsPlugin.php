<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Plugin\Sales\CustomerData;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\CustomerData\LastOrderedItems;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogPermissions\Model\Permission\Index;

/**
 * Get catalog permissions for products and verify it for last ordered items
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CheckCatalogPermissionAfterLastOrderedItemsPlugin
{
    /**
     * @var Index
     */
    private $permissionIndex;

    /**
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Index $permissionIndex
     * @param ConfigInterface $permissionsConfig
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Index $permissionIndex,
        ConfigInterface $permissionsConfig,
        Session $customerSession,
        StoreManagerInterface $storeManager
    ) {
        $this->permissionIndex = $permissionIndex;
        $this->permissionsConfig = $permissionsConfig;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
    }

    /**
     * Get catalog permissions per product ID
     *
     * @param LastOrderedItems $subject
     * @param array $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(LastOrderedItems $subject, array $result): array
    {
        if (!$this->permissionsConfig->isEnabled()) {
            return $result;
        }
        $customerGroupId = (int) $this->customerSession->getCustomerGroupId();
        $storeId = (int) $this->storeManager->getStore()->getId();
        if ($result && $result['items']) {
            foreach ($result['items'] as $key => $item) {
                $permissions = $this->permissionIndex->getIndexForProduct(
                    $item['product_id'],
                    $customerGroupId,
                    $storeId
                );
                if ($permissions) {
                    $grantCatalogCategoryViewPermission = $permissions[
                        $item['product_id']]['grant_catalog_category_view'];
                    if ($permissions[$item['product_id']] && $grantCatalogCategoryViewPermission !== "-1") {
                            unset($result['items'][$key]);
                    }
                } else {
                    unset($result['items'][$key]);
                }
            }
        }
        return $result;
    }
}
