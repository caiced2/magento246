<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\CatalogPermissions\Model\Permission;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CheckIfProductAllowedInRssObserver implements ObserverInterface
{
    /**
     * Catalog permission helper
     *
     * @var Data
     */
    protected $_catalogPermData;

    /**
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    protected $_permissionsConfig;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param Data $catalogPermData
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        Data $catalogPermData
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->_catalogPermData = $catalogPermData;
    }

    /**
     * Apply catalog permissions on product RSS feeds
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        $row = $observer->getEvent()->getRow();
        if (!$row) {
            $row = $observer->getEvent()->getProduct()->getData();
        }

        $observer->getEvent()->getProduct()->setAllowedInRss(
            $this->_checkPermission($row, 'grant_catalog_category_view', 'isAllowedCategoryView')
        );

        $observer->getEvent()->getProduct()->setAllowedPriceInRss(
            $this->_checkPermission($row, 'grant_catalog_product_price', 'isAllowedProductPrice')
        );

        return $this;
    }

    /**
     * Checks permission in passed product data.
     * For retrieving default configuration value used
     * $method from helper magento_catalogpermissions.
     *
     * @param array $data
     * @param string $permission
     * @param string $method method name from Data class
     * @return bool
     */
    protected function _checkPermission($data, $permission, $method)
    {
        /*
         * If there is no permissions for this
         * product then we will use configuration default
         */
        if (!array_key_exists($permission, $data)) {
            $data[$permission] = null;
        }

        if (!$this->_catalogPermData->{$method}()) {
            if ($data[$permission] == Permission::PERMISSION_ALLOW) {
                $result = true;
            } else {
                $result = false;
            }
        } else {
            if ($data[$permission] != Permission::PERMISSION_DENY || null === $data[$permission]) {
                $result = true;
            } else {
                $result = false;
            }
        }

        return $result;
    }
}
