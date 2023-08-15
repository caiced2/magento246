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

class ApplyProductPermissionObserver implements ObserverInterface
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
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    protected $_permissionsConfig;

    /**
     * @var ApplyPermissionsOnProduct
     */
    protected $applyPermissionsOnProduct;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param Session $customerSession
     * @param Index $permissionIndex
     * @param Data $catalogPermData
     * @param ApplyPermissionsOnProduct $applyPermissionsOnProduct
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        Session $customerSession,
        Index $permissionIndex,
        Data $catalogPermData,
        ApplyPermissionsOnProduct $applyPermissionsOnProduct
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->_catalogPermData = $catalogPermData;
        $this->_permissionIndex = $permissionIndex;
        $this->_customerSession = $customerSession;
        $this->applyPermissionsOnProduct = $applyPermissionsOnProduct;
    }

    /**
     * Apply product permissions on model after load
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

        $product = $observer->getEvent()->getProduct();
        $this->_permissionIndex->addIndexToProduct($product, $this->_customerSession->getCustomerGroupId());
        $this->applyPermissionsOnProduct->execute($product);
        if ($observer->getEvent()->getProduct()->getIsHidden()) {
            $observer->getEvent()->getControllerAction()->getResponse()->setRedirect(
                $this->_catalogPermData->getLandingPageUrl()
            );

            throw new \Magento\Framework\Exception\LocalizedException(
                __('You may need more permissions to access this product.')
            );
        }

        return $this;
    }
}
