<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Model\Staging;

use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Cart price rule staging preview store id resolver
 */
class PreviewStoreIdResolver
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Get default store ID for preview
     *
     * If provided websiteIds include default website, the default store id is returned.
     * Otherwise the default store id of the following website based on websites sort order is returned.
     *
     * @param array $websiteIds
     * @return int|null
     */
    public function execute(array $websiteIds): ?int
    {
        $storeId = null;
        $websites = $this->getWebsites($websiteIds);
        if (count($websites) > 0) {
            $website = reset($websites);
            $groups = $this->getStoreGroups($website);
            if (count($groups) > 0) {
                $group = reset($groups);
                $storeId = (int) $group->getDefaultStoreId();
            }
        }
        return $storeId;
    }

    /**
     * Get websites by IDs
     *
     * @param array $websiteIds
     * @return WebsiteInterface[]
     */
    private function getWebsites(array $websiteIds): array
    {
        $websites = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            if (in_array($website->getId(), $websiteIds)) {
                if ($website->getIsDefault()) {
                    $websites = [$website->getId() => $website] + $websites;
                } else {
                    $websites[$website->getId()] = $website;
                }
            }
        }

        return $websites;
    }

    /**
     * Get store groups for provided website.
     *
     * @param WebsiteInterface $website
     * @return GroupInterface[]
     */
    private function getStoreGroups(WebsiteInterface $website): array
    {
        $groups = [];
        foreach ($this->storeManager->getGroups() as $group) {
            if ($group->getWebsiteId() === $website->getId()) {
                if ($website->getDefaultGroupId() === $group->getId()) {
                    $groups = [$group->getId() => $group] + $groups;
                } else {
                    $groups[$group->getId()] = $group;
                }
            }
        }

        return $groups;
    }
}
