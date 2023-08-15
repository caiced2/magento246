<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Checks for permissions for quote items
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CheckQuotePermissionsObserver implements ObserverInterface
{
    /**
     * Permissions cache for products in cart
     *
     * @var array
     */
    protected $_permissionsQuoteCache = [];

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
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param Session $customerSession
     * @param Index $permissionIndex
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        Session $customerSession,
        Index $permissionIndex,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->_customerSession = $customerSession;
        $this->_permissionIndex = $permissionIndex;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Checks permissions for all quote items
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getCart()->getQuote();
        $allQuoteItems = $quote->getAllItems();
        $this->_initPermissionsOnQuoteItems($quote);

        foreach ($allQuoteItems as $quoteItem) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            if ($quoteItem->getParentItem()) {
                continue;
            }

            if ($quoteItem->getDisableAddToCart() && !$quoteItem->isDeleted()) {
                $quote->removeItem($quoteItem->getQuoteId());
                $quote->deleteItem($quoteItem);
                $quote->setHasError(
                    true
                )->addMessage(
                    __('You cannot add "%1" to the cart.', $quoteItem->getName())
                );
            }
        }

        return $this;
    }

    /**
     * Initialize permissions for quote items
     *
     * @param Quote $quote
     * @return $this
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _initPermissionsOnQuoteItems(Quote $quote)
    {
        $storeId = $quote->getStoreId();
        $customerGroupId = $this->_customerSession->getCustomerGroupId();
        $canCustomerAddToCart = $this->isConfigSettingsCanAddToCart($customerGroupId, $storeId);

        if ($this->canCheckoutGeneralConfigPermissions($customerGroupId, $storeId) &&
            $canCustomerAddToCart) {
            return $this;
        }

        $websiteId = $this->storeRepository->getById($storeId)->getWebsiteId();
        foreach ($quote->getAllItems() as $item) {
            $categoryIds = $item->getProduct()->getCategoryIds();

            if (empty($categoryIds)) {
                $item->setDisableAddToCart(true);
                continue;
            }

            $categoryPermissionIndex = $this->_permissionIndex->getIndexForCategory(
                $categoryIds,
                $customerGroupId,
                $websiteId
            );
            $permissions = [];
            foreach ($categoryPermissionIndex as $permission) {
                $permissions[] = (int)$permission['grant_checkout_items'];
            }

            if ((!empty($permissions) && !in_array(Permission::PERMISSION_ALLOW, $permissions, true)) ||
                (!$canCustomerAddToCart && empty($permissions))) {
                $item->setDisableAddToCart(true);
            }
        }

        return $this;
    }

    /**
     * Get Checkout Permissions from general config by Customer Group Id
     *
     * @param int $customerGroupId
     * @param int $storeId
     * @return bool
     */
    private function canCheckoutGeneralConfigPermissions(int $customerGroupId, int $storeId) : bool
    {
        if ((int)$this->_permissionsConfig->getCheckoutItemsMode($storeId) === ConfigInterface::GRANT_CUSTOMER_GROUP) {
            $grantCategoryView = in_array(
                $customerGroupId,
                $this->_permissionsConfig->getCatalogCategoryViewGroups($storeId)
            );
        } else {
            $viewMode = (int)$this->_permissionsConfig->getCatalogCategoryViewMode($storeId);
            $grantCategoryView = $viewMode === ConfigInterface::GRANT_ALL;
        }

        return $grantCategoryView;
    }

    /**
     * Check if current customer group can able to add product to cart
     *
     * @param int $customerGroupId
     * @param int $storeId
     * @return bool
     */
    private function isConfigSettingsCanAddToCart(int $customerGroupId, int $storeId) : bool
    {
        $canAddToCart = true;
        if ((int) $this->_permissionsConfig->getCheckoutItemsMode($storeId) === ConfigInterface::GRANT_CUSTOMER_GROUP) {
            $canAddToCart = in_array(
                $customerGroupId,
                $this->_permissionsConfig->getCheckoutItemsGroups($storeId)
            );
        }
        return $canAddToCart;
    }
}
