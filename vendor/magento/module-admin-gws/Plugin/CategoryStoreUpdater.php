<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Controller\Adminhtml\Category\Edit;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

/**
 * Updates store switcher on category edit form.
 */
class CategoryStoreUpdater
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Role $role
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Role $role,
        StoreManagerInterface $storeManager
    ) {
        $this->role = $role;
        $this->storeManager = $storeManager;
    }

    /**
     * Adds store to the request for a user with a scope restricted access
     *
     * @param Edit $subject
     */
    public function beforeExecute(Edit $subject)
    {
        if ($this->role->getIsAll() || empty($this->role->getWebsiteIds())) {
            return;
        }
        $storeId = $subject->getRequest()->getParam('store');
        if (!$storeId) {
            $userWebsiteIds = $this->role->getWebsiteIds();
            $subject->getRequest()->setParam(
                'store',
                $this->getFirstAllowedStoreId((int) reset($userWebsiteIds))
            );
        }
    }

    /**
     * Returns first allowed store id from the given website according to the current user role.
     *
     * @param int $websiteId
     * @return int|null
     * @throws LocalizedException
     */
    private function getFirstAllowedStoreId(int $websiteId)
    {
        /** @var Website $website */
        $website = $this->storeManager->getWebsite($websiteId);
        $storeIds = $website->getStoreIds();
        foreach ($storeIds as $storeId) {
            if ($this->role->hasStoreAccess($storeId)) {
                return (int)$storeId;
            }
        }

        return null;
    }
}
