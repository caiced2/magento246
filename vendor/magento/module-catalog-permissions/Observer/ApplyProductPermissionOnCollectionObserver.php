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

class ApplyProductPermissionOnCollectionObserver implements ObserverInterface
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
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    protected $_permissionsConfig;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param Session $customerSession
     * @param Index $permissionIndex
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        Session $customerSession,
        Index $permissionIndex
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->_permissionIndex = $permissionIndex;
        $this->_customerSession = $customerSession;
    }

    /**
     * Apply product permissions for collection
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        $collection = $observer->getEvent()->getCollection();
        $this->_permissionIndex->addIndexToProductCollection(
            $collection,
            $this->_customerSession->getCustomerGroupId()
        );
        return $this;
    }
}
