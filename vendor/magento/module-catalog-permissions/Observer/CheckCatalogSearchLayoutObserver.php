<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CheckCatalogSearchLayoutObserver implements ObserverInterface
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
     * Check catalog search availability on load layout
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        if (!$this->_catalogPermData->isAllowedCatalogSearch()) {
            $observer->getEvent()->getLayout()->getUpdate()->addHandle('CATALOGPERMISSIONS_DISABLED_CATALOG_SEARCH');
        }

        return $this;
    }
}
