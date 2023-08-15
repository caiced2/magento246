<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Permissions checker.
 *
 * @api
 * @since 100.0.2
 */
class Role extends DataObject
{
    /**
     * Store ACL role model instance
     *
     * @var \Magento\Authorization\Model\Role
     */
    protected $_adminRole;

    /**
     * Storage for disallowed entities and their ids
     *
     * @var array
     */
    protected $_disallowedWebsiteIds = [];

    /**
     * @var array
     */
    protected $_disallowedStores = [];

    /**
     * @var array
     */
    protected $_disallowedStoreIds = [];

    /**
     * @var array
     */
    protected $_disallowedStoreGroupIds = [];

    /**
     * @var array
     */
    protected $_disallowedStoreGroups = [];

    /**
     * Storage for categories which are used in allowed store groups
     *
     * @var array
     */
    protected $_allowedRootCategories;

    /**
     * Storage for categories which are not used in
     * disallowed store groups
     *
     * @var array
     */
    protected $_exclusiveRootCategories;

    /**
     * Storage for exclusive checked categories
     * using category path as key
     * @var array
     */
    protected $_exclusiveAccessToCategory = [];

    /**
     * @var CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $categoryCollectionFactory
     * @param array $data
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_storeManager = $storeManager;
        parent::__construct($data);
    }

    /**
     * Set ACL role and determine its limitations
     *
     * @param \Magento\Authorization\Model\Role $role
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function setAdminRole($role)
    {
        if ($role) {
            $this->_adminRole = $role;

            // find role disallowed data
            foreach ($this->_storeManager->getWebsites(true) as $websiteId => $website) {
                if (!in_array($websiteId, $this->getRelevantWebsiteIds())) {
                    $this->_disallowedWebsiteIds[] = $websiteId;
                }
            }
            foreach ($this->_storeManager->getStores(true) as $storeId => $store) {
                if (!in_array($storeId, $this->getStoreIds())) {
                    $this->_disallowedStores[] = $store;
                    $this->_disallowedStoreIds[] = $storeId;
                }
            }
            foreach ($this->_storeManager->getGroups(true) as $groupId => $group) {
                if (!in_array($groupId, $this->getStoreGroupIds())) {
                    $this->_disallowedStoreGroups[] = $group;
                    $this->_disallowedStoreGroupIds[] = $groupId;
                }
            }
        }
    }

    /**
     * Check whether GWS permissions are applicable
     *
     * True if all permissions are allowed or core
     * admin role model is not defined yet. So in result we can't restrict some
     * low level functionality.
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsAll()
    {
        if ($this->_adminRole) {
            return $this->_adminRole->getGwsIsAll();
        }

        return true;
    }

    /**
     * Checks whether GWS permissions on website level
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsWebsiteLevel()
    {
        $_websiteIds = $this->getWebsiteIds();
        return !empty($_websiteIds);
    }

    /**
     * Checks whether GWS permissions on store level
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsStoreLevel()
    {
        $_websiteIds = $this->getWebsiteIds();
        return empty($_websiteIds);
    }

    /**
     * Get allowed store ids from core admin role object
     *
     * If role model is not defined yet use default value as empty array.
     *
     * @return array
     */
    public function getStoreIds()
    {
        if ($this->_adminRole) {
            $gwsStores = $this->_adminRole->getGwsStores();
            return is_array($gwsStores) ? $gwsStores : [];
        }

        return [];
    }

    /**
     * Set allowed store ids for the core admin role object in session
     *
     * If role model is not defined yet do nothing.
     *
     * @param mixed $value
     * @return array|$this
     */
    public function setStoreIds($value)
    {
        if ($this->_adminRole) {
            return $this->_adminRole->setGwsStores($value);
        }

        return $this;
    }

    /**
     * Get allowed store group ids from core admin role object
     *
     * If role model is not defined yet use default value as empty array.
     *
     * @return array
     */
    public function getStoreGroupIds()
    {
        if ($this->_adminRole) {
            $gwsStoreGroups = $this->_adminRole->getGwsStoreGroups();
            return is_array($gwsStoreGroups) ? $gwsStoreGroups : [];
        }

        return [];
    }

    /**
     * Set allowed store group ids for the core admin role object in session
     *
     * If role model is not defined yet do nothing.
     *
     * @param mixed $value
     * @return array|$this
     */
    public function setStoreGroupIds($value)
    {
        if ($this->_adminRole) {
            return $this->_adminRole->setGwsStoreGroups($value);
        }

        return $this;
    }

    /**
     * Get allowed website ids from core admin role object
     *
     * If role model is not defined yeat use default value as empty array.
     *
     * @return array
     */
    public function getWebsiteIds()
    {
        if ($this->_adminRole) {
            $gwsWebsites = $this->_adminRole->getGwsWebsites();
            return is_array($gwsWebsites) ? $gwsWebsites : [];
        }

        return [];
    }

    /**
     * Get website ids of allowed store groups
     *
     * @return array
     */
    public function getRelevantWebsiteIds()
    {
        if ($this->_adminRole) {
            $websiteIds = $this->_adminRole->getGwsRelevantWebsites();
            return is_array($websiteIds) ? $websiteIds : [];
        }

        return [];
    }

    /**
     * Get website IDs that are not allowed
     *
     * @return array
     */
    public function getDisallowedWebsiteIds()
    {
        return $this->_disallowedWebsiteIds;
    }

    /**
     * Get store IDs that are not allowed
     *
     * @return array
     */
    public function getDisallowedStoreIds()
    {
        $result = [];

        foreach ($this->_disallowedStores as $store) {
            $result[] = $store->getId();
        }

        return $result;
    }

    /**
     * Get stores that are not allowed
     *
     * @return array
     */
    public function getDisallowedStores()
    {
        return $this->_disallowedStores;
    }

    /**
     * Get root categories that are allowed in current permissions scope
     *
     * @return array
     */
    public function getAllowedRootCategories()
    {
        if (!$this->getIsAll() && null === $this->_allowedRootCategories) {
            $this->_allowedRootCategories = [];

            $categoryIds = [];
            if (!empty($this->getWebsiteIds())) {
                foreach ($this->getStoreGroupIds() as $groupId) {
                    $categoryIds[] = $this->getGroup($groupId)->getRootCategoryId();
                }
            }

            $categories = $this->_categoryCollectionFactory->create()->addIdFilter($categoryIds);
            foreach ($categories as $category) {
                $this->_allowedRootCategories[$category->getId()] = $category->getPath();
            }
        }
        return $this->_allowedRootCategories;
    }

    /**
     * Get exclusive root categories that are allowed in current permissions scope
     *
     * @return array
     */
    public function getExclusiveRootCategories()
    {
        if (!$this->getIsAll() && null === $this->_exclusiveRootCategories) {
            $this->_exclusiveRootCategories = $this->getAllowedRootCategories();
            foreach ($this->_disallowedStoreGroups as $group) {
                $_catId = $group->getRootCategoryId();

                $pos = array_search($_catId, array_keys($this->_exclusiveRootCategories));
                if ($pos !== false) {
                    unset($this->_exclusiveRootCategories[$_catId]);
                }
            }
        }
        return $this->_exclusiveRootCategories;
    }

    /**
     * Check if current user have exclusive access to specified category (by path)
     *
     * @param string $categoryPath
     * @return bool
     */
    public function hasExclusiveCategoryAccess($categoryPath)
    {
        if (!$categoryPath) {
            return false;
        }

        if (!isset($this->_exclusiveAccessToCategory[$categoryPath])) {
            /**
             * By default we grant permissions for category
             */
            $result = true;

            if (!$this->getIsAll()) {
                $categoryPathArray = explode('/', $categoryPath);
                if (count($categoryPathArray) < 2) {
                    //not grant access if category is root
                    $result = false;
                } else {
                    if (count(
                        array_intersect($categoryPathArray, array_keys($this->getExclusiveRootCategories()))
                    ) == 0
                    ) {
                        $result = false;
                    }
                }
            }
            $this->_exclusiveAccessToCategory[$categoryPath] = $result;
        }

        return $this->_exclusiveAccessToCategory[$categoryPath];
    }

    /**
     * Check whether specified website ID is allowed
     *
     * @param string|int|array $websiteId
     * @param bool $isExplicit
     * @return bool
     */
    public function hasWebsiteAccess($websiteId, $isExplicit = false)
    {
        $websitesToCompare = $this->getRelevantWebsiteIds();
        if ($isExplicit) {
            $websitesToCompare = $this->getWebsiteIds();
        }
        if (is_array($websiteId)) {
            return count(array_intersect($websiteId, $websitesToCompare)) > 0;
        }
        return in_array($websiteId, $websitesToCompare);
    }

    /**
     * Check whether specified store ID is allowed
     *
     * @param string|int|array $storeId
     * @return bool
     */
    public function hasStoreAccess($storeId)
    {
        if (is_array($storeId)) {
            return count(array_intersect($storeId, $this->getStoreIds())) > 0;
        }
        return in_array($storeId, $this->getStoreIds());
    }

    /**
     * Check whether specified store group ID is allowed
     *
     * @param string|int|array $storeGroupId
     * @return bool
     */
    public function hasStoreGroupAccess($storeGroupId)
    {
        if (is_array($storeGroupId)) {
            return count(array_intersect($storeGroupId, $this->getStoreGroupIds())) > 0;
        }
        return in_array($storeGroupId, $this->getStoreGroupIds());
    }

    /**
     * Check whether website access is exlusive
     *
     * @param array $websiteIds
     * @return bool
     */
    public function hasExclusiveAccess($websiteIds)
    {
        return $this->getIsAll() || count(
            array_intersect($this->getWebsiteIds(), $websiteIds)
        ) === count(
            $websiteIds
        ) && $this->getIsWebsiteLevel();
    }

    /**
     * Check whether store access is exlusive
     *
     * @param array $storeIds
     * @return bool
     */
    public function hasExclusiveStoreAccess($storeIds)
    {
        return $this->getIsAll() || count(array_intersect($this->getStoreIds(), $storeIds)) === count($storeIds);
    }

    /**
     * Find a store group by id
     * Note: For case when we can't $this->_storeManager->getGroup() bc it will try to load
     * store group in case store group is not preloaded
     *
     * @param int|string $findGroupId
     * @return \Magento\Store\Model\Group|null
     */
    public function getGroup($findGroupId)
    {
        foreach ($this->_storeManager->getGroups() as $groupId => $group) {
            if ($findGroupId == $groupId) {
                return $group;
            }
        }

        return null;
    }
}
