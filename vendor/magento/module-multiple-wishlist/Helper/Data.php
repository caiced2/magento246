<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Model\ResourceModel\Item\Collection;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistItemCollection;
use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection as WishlistCollection;

/**
 * Multiple wishlist helper
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Wishlist\Helper\Data
{
    /**
     * The list of default wishlists grouped by customer id
     *
     * @var array
     */
    protected $_defaultWishlistsByCustomer = [];

    /**
     * Item collection factory
     *
     * @var WishlistItemCollection
     */
    protected $_itemCollectionFactory;

    /**
     * Wishlist collection factory
     *
     * @var CollectionFactory
     */
    protected $_wishlistCollectionFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Session $customerSession
     * @param WishlistFactory $wishlistFactory
     * @param StoreManagerInterface $storeManager
     * @param PostHelper $postDataHelper
     * @param View $customerViewHelper
     * @param WishlistProviderInterface $wishlistProvider
     * @param ProductRepositoryInterface $productRepository
     * @param WishlistItemCollection $itemCollectionFactory
     * @param CollectionFactory $wishlistCollectionFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Session $customerSession,
        WishlistFactory $wishlistFactory,
        StoreManagerInterface $storeManager,
        PostHelper $postDataHelper,
        View $customerViewHelper,
        WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepository,
        WishlistItemCollection $itemCollectionFactory,
        CollectionFactory $wishlistCollectionFactory
    ) {
        $this->_itemCollectionFactory = $itemCollectionFactory;
        $this->_wishlistCollectionFactory = $wishlistCollectionFactory;
        parent::__construct(
            $context,
            $coreRegistry,
            $customerSession,
            $wishlistFactory,
            $storeManager,
            $postDataHelper,
            $customerViewHelper,
            $wishlistProvider,
            $productRepository
        );
    }

    /**
     * Create wishlist item collection
     *
     * @return Collection
     * @throws LocalizedException
     */
    protected function _createWishlistItemCollection()
    {
        if ($this->isMultipleEnabled()) {
            return $this->_itemCollectionFactory->create()->addCustomerIdFilter(
                $this->getCustomer()->getId()
            )->addStoreFilter(
                $this->_storeManager->getWebsite()->getStoreIds()
            )->setVisibilityFilter();
        } else {
            return parent::_createWishlistItemCollection();
        }
    }

    /**
     * Check whether multiple wishlist is enabled
     *
     * @return bool
     */
    public function isMultipleEnabled()
    {
        return $this->_moduleManager->isOutputEnabled($this->_getModuleName()) && $this->scopeConfig->getValue(
                'wishlist/general/active',
                ScopeInterface::SCOPE_STORE
            ) && $this->scopeConfig->getValue(
                'wishlist/general/multiple_enabled',
                ScopeInterface::SCOPE_STORE
            );
    }

    /**
     * Check whether given wishlist is default for it's customer
     *
     * @param Wishlist $wishlist
     * @return bool
     * @throws LocalizedException
     */
    public function isWishlistDefault(Wishlist $wishlist)
    {
        return $this->getDefaultWishlist($wishlist->getCustomerId())->getId() == $wishlist->getId();
    }

    /**
     * Retrieve customer's default wishlist
     *
     * @param  $customerId
     * @return Wishlist
     */
    public function getDefaultWishlist($customerId = null)
    {
        if (!$customerId && $this->getCustomer()) {
            $customerId = $this->getCustomer()->getId();
        }
        if (!isset($this->_defaultWishlistsByCustomer[$customerId])) {
            $this->_defaultWishlistsByCustomer[$customerId] = $this->_wishlistFactory->create();
            $this->_defaultWishlistsByCustomer[$customerId]->loadByCustomerId($customerId, false);
        }
        return $this->_defaultWishlistsByCustomer[$customerId];
    }

    /**
     * Get max allowed number of wishlists per customers
     *
     * @return int
     */
    public function getWishlistLimit()
    {
        return $this->scopeConfig->getValue(
            'wishlist/general/multiple_wishlist_number',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check whether given wishlist collection size exceeds wishlist limit
     *
     * @param WishlistCollection $wishlistList
     * @return bool
     */
    public function isWishlistLimitReached(WishlistCollection $wishlistList)
    {
        return count($wishlistList) >= $this->getWishlistLimit();
    }

    /**
     * Retrieve Wishlist collection by customer id
     *
     * @param  $customerId
     * @return WishlistCollection
     * @throws LocalizedException
     */
    public function getCustomerWishlists($customerId = null)
    {
        if (!$customerId && $this->getCustomer()) {
            $customerId = $this->getCustomer()->getId();
        }
        $wishlistsByCustomer = $this->_coreRegistry->registry('wishlists_by_customer');
        if (!isset($wishlistsByCustomer[$customerId])) {
            /** @var WishlistCollection $collection */
            $collection = $this->_wishlistCollectionFactory->create();
            $collection->filterByCustomerId($customerId);
            if ($customerId && !$collection->getItems()) {
                $wishlist = $this->addWishlist($customerId);
                $collection->addItem($wishlist);
            }
            $wishlistsByCustomer[$customerId] = $collection;
            $this->_coreRegistry->register('wishlists_by_customer', $wishlistsByCustomer);
        }
        return $wishlistsByCustomer[$customerId];
    }

    /**
     * Create new wishlist
     *
     * @param int $customerId
     * @return Wishlist
     * @throws LocalizedException
     */
    protected function addWishlist($customerId)
    {
        $wishlist = $this->_wishlistFactory->create();
        $wishlist->setCustomerId($customerId);
        $wishlist->generateSharingCode();
        $wishlist->save();
        return $wishlist;
    }

    /**
     * Retrieve number of wishlist items in given wishlist
     *
     * @param Wishlist $wishlist
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWishlistItemCount(Wishlist $wishlist)
    {
        $collection = $wishlist->getItemCollection()->setInStockFilter(true);
        if ($this->scopeConfig->getValue(
            self::XML_PATH_WISHLIST_LINK_USE_QTY,
            ScopeInterface::SCOPE_STORE
        )
        ) {
            $count = $collection->getItemsQty();
        } else {
            $count = $collection->getSize();
        }
        return $count;
    }
}
