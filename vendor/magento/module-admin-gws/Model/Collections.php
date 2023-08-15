<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdminGws\Model;

use Magento\Customer\Model\Config\Share as ShareConfig;
use Magento\Framework\App\ObjectManager;

/**
 * Collections limiter model
 *
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Collections extends \Magento\AdminGws\Model\Observer\AbstractObserver implements CallbackProcessorInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager = null;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_backendAuthSession;

    /**
     * @var \Magento\AdminGws\Model\ResourceModel\CollectionsFactory
     */
    protected $_collectionsFactory;

    /**
     * @var ShareConfig
     */
    private $shareConfig;

    /**
     * @param \Magento\AdminGws\Model\Role $role
     * @param \Magento\AdminGws\Model\ResourceModel\CollectionsFactory $collectionsFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ShareConfig|null $shareConfig
     */
    public function __construct(
        \Magento\AdminGws\Model\Role $role,
        \Magento\AdminGws\Model\ResourceModel\CollectionsFactory $collectionsFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ShareConfig $shareConfig = null
    ) {
        $this->_collectionsFactory = $collectionsFactory;
        $this->_backendAuthSession = $backendAuthSession;
        $this->_storeManager = $storeManager;
        $this->shareConfig = $shareConfig ?: ObjectManager::getInstance()->get(ShareConfig::class);
        parent::__construct($role);
    }

    /**
     * Limit store views collection.
     *
     * Adding limitation depending on allowed group ids for user.
     *
     * @param \Magento\Store\Model\ResourceModel\Store\Collection $collection
     * @return void
     */
    public function limitStores($collection)
    {
        // Changed from filter by store id bc of case when
        // user creating new store view for allowed store group
        $collection->addGroupFilter(array_merge($this->_role->getStoreGroupIds(), [0]));
    }

    /**
     * Limit websites collection
     *
     * @param \Magento\Store\Model\ResourceModel\Website\Collection $collection
     * @return void
     */
    public function limitWebsites($collection)
    {
        $collection->addIdFilter(array_merge($this->_role->getRelevantWebsiteIds(), [0]));
        $collection->addFilterByGroupIds(array_merge($this->_role->getStoreGroupIds(), [0]));
    }

    /**
     * Limit store groups collection
     *
     * @param \Magento\Store\Model\ResourceModel\Group\Collection $collection
     * @return void
     */
    public function limitStoreGroups($collection)
    {
        $collection->addFieldToFilter(
            'group_id',
            ['in' => array_merge($this->_role->getStoreGroupIds(), [0])]
        );
    }

    /**
     * Limit a collection by allowed stores without admin
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return void
     */
    public function addStoreFilterNoAdmin($collection)
    {
        $collection->addStoreFilter($this->_role->getStoreIds(), false);
    }

    /**
     * Add filter by store views to a collection
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return void
     */
    public function addStoreFilter($collection)
    {
        $collection->addStoreFilter($this->_role->getStoreIds());
    }

    /**
     * Limit products collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function limitProducts($collection)
    {
        $relevantWebsiteIds = $this->_role->getRelevantWebsiteIds();
        $websiteIds = [];
        $filters = $collection->getLimitationFilters();

        if (isset($filters['website_ids'])) {
            $websiteIds = (array)$filters['website_ids'];
        }
        if (isset($filters['store_id'])) {
            $websiteIds[] = $this->_storeManager->getStore($filters['store_id'])->getWebsiteId();
        }

        if (count($websiteIds)) {
            $collection->addWebsiteFilter(array_intersect($websiteIds, $relevantWebsiteIds));
        } else {
            $collection->addWebsiteFilter($relevantWebsiteIds);
        }
    }

    /**
     * Limit customers collection
     *
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $collection
     * @return void
     */
    public function limitCustomers($collection)
    {
        $collection->addAttributeToFilter(
            'website_id',
            ['website_id' => ['in' => $this->_role->getRelevantWebsiteIds()]]
        );
    }

    /**
     * Limit online visitor log collection
     *
     * @param \Magento\Customer\Model\ResourceModel\Visitor\Online\Collection $collection
     * @return void
     *
     * @deprecated 100.1.0
     */
    public function limitOnlineCustomers($collection)
    {
        $collection->addWebsiteFilter($this->_role->getRelevantWebsiteIds());
    }

    /**
     * Limit customers collection
     *
     * @param \Magento\Customer\Model\ResourceModel\Grid\Collection $collection
     * @return void
     * @since 100.1.0
     */
    public function addCustomerWebsiteFilter($collection)
    {
        if ($this->shareConfig->isWebsiteScope()) {
            $collection->addFieldToFilter(
                'website_id',
                ['website_id' => ['in' => $this->_role->getRelevantWebsiteIds()]]
            );
        }
    }

    /**
     * Limit reviews collection
     *
     * @param \Magento\Review\Model\ResourceModel\Review\Collection $collection
     * @return void
     */
    public function limitReviews($collection)
    {
        $collection->addStoreFilter($this->_role->getStoreIds());
    }

    /**
     * Limit product reviews collection
     *
     * @param \Magento\Review\Model\ResourceModel\Review\Product\Collection $collection
     * @return void
     */
    public function limitProductReviews($collection)
    {
        $collection->setStoreFilter($this->_role->getStoreIds());
    }

    /**
     * Limit GCA collection
     *
     * @param \Magento\GiftCardAccount\Model\ResourceModel\Giftcardaccount\Collection $collection
     * @return void
     */
    public function limitGiftCardAccounts($collection)
    {
        $collection->addWebsiteFilter($this->_role->getRelevantWebsiteIds());
    }

    /**
     * Limit Reward Points history collection
     *
     * @param \Magento\Reward\Model\ResourceModel\Reward\History\Collection $collection
     * @return void
     */
    public function limitRewardHistoryWebsites($collection)
    {
        $collection->addWebsiteFilter($this->_role->getRelevantWebsiteIds());
    }

    /**
     * Limit Reward Points balance collection
     *
     * @param \Magento\Reward\Model\ResourceModel\Reward\Collection $collection
     * @return void
     */
    public function limitRewardBalanceWebsites($collection)
    {
        $collection->addWebsiteFilter($this->_role->getRelevantWebsiteIds());
    }

    /**
     * Limit store credit collection
     *
     * @param \Magento\CustomerBalance\Model\ResourceModel\Balance\Collection $collection
     * @return void
     */
    public function limitStoreCredits($collection)
    {
        $collection->addWebsitesFilter($this->_role->getRelevantWebsiteIds());
    }

    /**
     * Limit store credit collection
     *
     * @param \Magento\CustomerBalance\Model\ResourceModel\Balance\History\Collection $collection
     * @return void
     */
    public function limitStoreCreditsHistory($collection)
    {
        $collection->addWebsitesFilter($this->_role->getRelevantWebsiteIds());
    }

    /**
     * Limit Catalog events collection
     *
     * @param \Magento\CatalogEvent\Model\ResourceModel\Event\Collection $collection
     * @return void
     */
    public function limitCatalogEvents($collection)
    {
        $collection->capByCategoryPaths($this->_role->getAllowedRootCategories());
    }

    /**
     * Limit catalog categories collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $collection
     * @return void
     */
    public function limitCatalogCategories($collection)
    {
        $collection->addPathsFilter($this->_role->getAllowedRootCategories());
    }

    /**
     * Limit core URL rewrites
     *
     * @param \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection $collection
     * @return void
     */
    public function limitUrlRewrites($collection)
    {
        $collection->addStoreFilter($this->_role->getStoreIds(), false);
    }

    /**
     * Limit ratings collection
     *
     * @param \Magento\Review\Model\ResourceModel\Rating\Collection $collection
     * @return void
     */
    public function limitRatings($collection)
    {
        $collection->setStoreFilter($this->_role->getStoreIds());
    }

    /**
     * Add store_id attribute to filter of EAV-collection
     *
     * @param \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection
     * @return void
     */
    public function addStoreAttributeToFilter($collection)
    {
        $collection->addAttributeToFilter('store_id', ['in' => $this->_role->getStoreIds()]);
    }

    /**
     * Add store_id field to filter
     *
     * @param \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection
     * @return void
     * @since 100.1.0
     */
    public function addStoreFieldToFilter($collection)
    {
        $collection->addFieldToFilter('store_id', ['in' => $this->_role->getStoreIds()]);
    }

    /**
     * Filter checkout agreements collection by allowed stores
     *
     * @param \Magento\CheckoutAgreements\Model\ResourceModel\Agreement\Collection $collection
     * @return void
     */
    public function limitCheckoutAgreements($collection)
    {
        $collection->setIsStoreFilterWithAdmin(false)->addStoreFilter($this->_role->getStoreIds());
    }

    /**
     * Filter admin roles collection by allowed stores
     *
     * @param \Magento\Authorization\Model\ResourceModel\Role\Collection $collection
     * @return void
     */
    public function limitAdminPermissionRoles($collection)
    {
        $limited = $this->_collectionsFactory->create()->getRolesOutsideLimitedScope(
            $this->_role->getIsAll(),
            $this->_role->getWebsiteIds(),
            $this->_role->getStoreGroupIds()
        );

        if (is_array($limited) && !empty($limited)) {
            $collection->addFieldToFilter('role_id', ['nin' => $limited]);
        }
    }

    /**
     * Filter admin users collection by allowed stores
     *
     * @param \Magento\User\Model\ResourceModel\User\Collection $collection
     * @return void
     */
    public function limitAdminPermissionUsers($collection)
    {
        $limited = $this->_collectionsFactory->create()->getUsersOutsideLimitedScope(
            $this->_role->getIsAll(),
            $this->_role->getWebsiteIds(),
            $this->_role->getStoreGroupIds()
        );

        if (is_array($limited) && !empty($limited)) {
            $collection->addFieldToFilter('main_table.user_id', ['nin' => $limited]);
        }
    }

    /**
     * Filter sales collection by allowed stores
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function addSalesSaleCollectionStoreFilter($observer)
    {
        $collection = $observer->getEvent()->getCollection();

        $this->addStoreFilter($collection);
    }

    /**
     * Filter sold product report collection by allowed stores
     *
     * @param \Magento\Reports\Model\ResourceModel\Product\Sold\Collection $collection
     * @since 100.3.1
     */
    public function addReportCollectionStoreFilter($collection)
    {
        $collection->setStoreIds($this->_role->getStoreIds());
    }

    /**
     * Apply store filter on collection used in new order's rss
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function rssOrderNewCollectionSelect($observer)
    {
        $collection = $observer->getEvent()->getCollection();
        $this->addStoreAttributeToFilter($collection);
        return $this;
    }

    /**
     * Sets admin role. This is vital for limitProducts(), otherwise getRelevantWebsiteIds() returns an empty array.
     *
     * @return $this
     */
    protected function _initRssAdminRole()
    {
        /* @var $adminUser \Magento\User\Model\User */
        $adminUser = $this->_backendAuthSession->getUser();
        if ($adminUser) {
            $this->_role->setAdminRole($adminUser->getRole());
        }
        return $this;
    }

    /**
     * Apply websites filter on collection used in notify stock rss
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function rssCatalogNotifyStockCollectionSelect($observer)
    {
        $collection = $observer->getEvent()->getCollection();
        $this->_initRssAdminRole()->limitProducts($collection);
        return $this;
    }

    /**
     * Apply websites filter on collection used in review rss
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function rssCatalogReviewCollectionSelect($observer)
    {
        $collection = $observer->getEvent()->getCollection();
        $this->_initRssAdminRole()->limitProducts($collection);
        return $this;
    }

    /**
     * Limit product reports
     *
     * @param  \Magento\Reports\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function limitProductReports($collection)
    {
        $collection->addStoreRestrictions($this->_role->getStoreIds(), $this->_role->getRelevantWebsiteIds());
    }

    /**
     * Limit GiftRegistry Entity collection
     *
     * @param \Magento\GiftRegistry\Model\ResourceModel\Entity\Collection $collection
     * @return void
     */
    public function limitGiftRegistryEntityWebsites($collection)
    {
        $collection->addWebsiteFilter($this->_role->getRelevantWebsiteIds());
    }

    /**
     * Limit bestsellers collection
     *
     * @param \Magento\Sales\Model\ResourceModel\Report\Bestsellers\Collection $collection
     * @return void
     */
    public function limitBestsellersCollection($collection)
    {
        $collection->addStoreRestrictions($this->_role->getStoreIds());
    }

    /**
     * Limit most viewed collection
     *
     * @param \Magento\Reports\Model\ResourceModel\Report\Product\Viewed\Collection $collection
     * @return void
     */
    public function limitMostViewedCollection($collection)
    {
        $collection->addStoreRestrictions($this->_role->getStoreIds());
    }

    /**
     * Limit Automated Email Marketing Reminder Rules collection
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return void
     */
    public function limitRuleEntityCollection($collection)
    {
        $collection->addWebsiteFilter($this->_role->getRelevantWebsiteIds());
    }
}
