<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Backend\Block\System\Store\Store;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class is responsible for filtering Websites, Store and Store Views according to Admin permissions
 */
class StoreFilter
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @param Role $role
     */
    public function __construct(
        Role $role
    ) {
        $this->role = $role;
    }

    /**
     * Retrieve stores array
     *
     * @param StoreManagerInterface $storeManager
     * @param array $stores
     * @param bool $withDefault
     * @param bool $codeKey
     * @return StoreInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetStores(
        StoreManagerInterface $storeManager,
        $stores,
        $withDefault = false,
        $codeKey = false
    ) {
        if ($this->role->getIsAll()) {
            return $stores;
        }

        $roleStoreIds = $this->role->getStoreIds();
        foreach ($stores as $key => $store) {
            if (!in_array($store->getId(), $roleStoreIds)) {
                unset($stores[$key]);
            }
        }
        return $stores;
    }

    /**
     * Retrieve array of store groups
     *
     * @param StoreManagerInterface $storeManager
     * @param array $groups
     * @param bool $withDefault
     * @return GroupInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetGroups(StoreManagerInterface $storeManager, $groups, $withDefault = false)
    {
        if ($this->role->getIsAll()) {
            return $groups;
        }

        $roleGroupIds = $this->role->getStoreGroupIds();
        foreach ($groups as $key => $group) {
            if (!in_array($group->getId(), $roleGroupIds)) {
                unset($groups[$key]);
            }
        }
        return $groups;
    }

    /**
     * Get loaded websites
     *
     * @param StoreManagerInterface $storeManager
     * @param array $websites
     * @param bool $withDefault
     * @param bool $codeKey
     * @return WebsiteInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetWebsites(
        StoreManagerInterface $storeManager,
        $websites,
        $withDefault = false,
        $codeKey = false
    ) {
        if ($this->role->getIsAll()) {
            return $websites;
        }

        $roleRelevantWebsiteIds = $this->role->getRelevantWebsiteIds();
        foreach ($websites as $key => $website) {
            if (!in_array($website->getId(), $roleRelevantWebsiteIds)) {
                unset($websites[$key]);
            }
        }
        return $websites;
    }

    /**
     * Retrieve default store for default group and website
     *
     * @param StoreManagerInterface $storeManager
     * @param StoreInterface|null $defaultStore
     *
     * @return StoreInterface|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException
     */
    public function afterGetDefaultStoreView(StoreManagerInterface $storeManager, $defaultStore)
    {
        if ($this->role->getIsAll()) {
            return $defaultStore;
        }

        return $defaultStore && $this->role->hasStoreAccess($defaultStore->getId()) ?
            $defaultStore : $this->getAnyAllowedStore($storeManager);
    }

    /**
     * Get any allowed store view.
     *
     * @param StoreManagerInterface $storeManager
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getAnyAllowedStore(StoreManagerInterface $storeManager): StoreInterface
    {
        $firstStoreId = current($this->role->getStoreIds());
        return $storeManager->getStore($firstStoreId);
    }
}
