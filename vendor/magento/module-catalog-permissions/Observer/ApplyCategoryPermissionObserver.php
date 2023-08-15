<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class ApplyCategoryPermissionObserver implements ObserverInterface
{
    /**
     * Catalog permission helper
     *
     * @var Data
     */
    protected $_catalogPermData;

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
     * @param Data $catalogPermData
     * @param ApplyPermissionsOnCategory $applyPermissionsOnCategory
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Session $customerSession,
        Index $permissionIndex,
        Data $catalogPermData,
        ApplyPermissionsOnCategory $applyPermissionsOnCategory
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->_storeManager = $storeManager;
        $this->_catalogPermData = $catalogPermData;
        $this->_permissionIndex = $permissionIndex;
        $this->_customerSession = $customerSession;
        $this->applyPermissionsOnCategory = $applyPermissionsOnCategory;
    }

    /**
     * Applies category permission on model afterload
     *
     * @param EventObserver $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        $category = $observer->getEvent()->getCategory();
        $permissions = $this->_permissionIndex->getIndexForCategory(
            $category->getId(),
            $this->_customerSession->getCustomerGroupId(),
            $this->_storeManager->getStore()->getWebsiteId()
        );

        if (isset($permissions[$category->getId()])) {
            $category->setPermissions($permissions[$category->getId()]);
        }

        $this->applyPermissionsOnCategory->execute($category);
        if ($observer->getEvent()->getCategory()->getIsHidden()) {
            $observer->getEvent()->getControllerAction()->getResponse()->setRedirect(
                $this->_catalogPermData->getLandingPageUrl()
            );

            throw new \Magento\Framework\Exception\LocalizedException(
                __('You may need more permissions to access this category.')
            );
        }
        return $this;
    }
}
