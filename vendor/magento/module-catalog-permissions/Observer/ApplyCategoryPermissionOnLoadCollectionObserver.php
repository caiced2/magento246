<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class ApplyCategoryPermissionOnLoadCollectionObserver implements ObserverInterface
{
    /**
     * Permissions index instance
     *
     * @var Index
     */
    protected $_permissionIndex;

    /**
     * Customer session instance
     *
     * @var Session
     */
    protected $_customerSession;

    /**
     * Store manager instance
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    protected $_permissionsConfig;

    /**
     * @var ApplyPermissionsOnCategory
     */
    protected $applyPermissionsOnCategory;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param Index $permissionIndex
     * @param ApplyPermissionsOnCategory $applyPermissionsOnCategory
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Session $customerSession,
        Index $permissionIndex,
        ApplyPermissionsOnCategory $applyPermissionsOnCategory
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->_storeManager = $storeManager;
        $this->_permissionIndex = $permissionIndex;
        $this->_customerSession = $customerSession;
        $this->applyPermissionsOnCategory = $applyPermissionsOnCategory;
    }

    /**
     * Apply category permissions for category collection
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        $permissions = [];
        $categoryCollection = $observer->getEvent()->getCategoryCollection();
        $categoryIds = $categoryCollection->getColumnValues('entity_id');

        if ($categoryIds) {
            $permissions = $this->_permissionIndex->getIndexForCategory(
                $categoryIds,
                $this->_customerSession->getCustomerGroupId(),
                $this->_storeManager->getStore()->getWebsiteId()
            );
        }

        foreach ($permissions as $categoryId => $permission) {
            $categoryCollection->getItemById($categoryId)->setPermissions($permission);
        }

        foreach ($categoryCollection as $category) {
            $this->applyPermissionsOnCategory->execute($category);
        }

        return $this;
    }
}
