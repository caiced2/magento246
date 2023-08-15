<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\LoadPermissionsForProductCollection;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Load product catalog permissions
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ApplyProductPermissionOnCollectionAfterLoadObserver implements ObserverInterface
{
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
     * @var Session
     */
    private $customerSession;
    /**
     * @var LoadPermissionsForProductCollection
     */
    private $loadPermissionsForProductCollection;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param ApplyPermissionsOnProduct $applyPermissionsOnProduct
     * @param Session $customerSession
     * @param LoadPermissionsForProductCollection $loadPermissionsForProductCollection
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        ApplyPermissionsOnProduct $applyPermissionsOnProduct,
        Session $customerSession,
        LoadPermissionsForProductCollection $loadPermissionsForProductCollection
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->applyPermissionsOnProduct = $applyPermissionsOnProduct;
        $this->customerSession = $customerSession;
        $this->loadPermissionsForProductCollection = $loadPermissionsForProductCollection;
    }

    /**
     * Apply category permissions for collection on after load
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        /** @var Collection $collection */
        $collection = $observer->getEvent()->getCollection();
        $hasPermissions = $this->hasPermissions($collection);
        $permissions = [];
        if (!$hasPermissions) {
            $permissions = $this->loadPermissionsForProductCollection->execute(
                $collection,
                (int) $this->customerSession->getCustomerGroupId(),
                (int) $collection->getStoreId()
            );
        }
        foreach ($collection as $product) {
            if ($collection->hasFlag('product_children')) {
                $product->addData(
                    [
                        'grant_catalog_category_view' => -1,
                        'grant_catalog_product_price' => -1,
                        'grant_checkout_items' => -1
                    ]
                );
            } elseif (!$hasPermissions && isset($permissions[$product->getId()])) {
                $product->addData($permissions[$product->getId()]);
            }
            $this->applyPermissionsOnProduct->execute($product);
        }
        return $this;
    }

    /**
     * Check if collection items have catalog permission fields
     *
     * @param Collection $collection
     * @return bool
     */
    private function hasPermissions($collection): bool
    {
        $hasPermissions = true;
        foreach ($collection as $product) {
            $hasPermissions = (
                $product->hasGrantCatalogCategoryView()
                && $product->hasGrantCatalogCategoryPrice()
                && $product->hasCheckoutItems()
            );
            if (!$hasPermissions) {
                break;
            }
        }
        return $hasPermissions;
    }
}
