<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Banner\Model\Banner;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Banner\Model\Config;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Banner section.
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data implements SectionSourceInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Store Banner resource instance
     *
     * @var \Magento\Banner\Model\ResourceModel\Banner
     */
    protected $bannerResource;

    /**
     * Banner instance
     *
     * @var \Magento\Banner\Model\Banner
     */
    protected $banner;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * @var array
     */
    protected $banners = [];

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var array
     */
    private $bannersBySalesRule;

    /**
     * @var array
     */
    private $bannersByCatalogRule;

    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\Rule
     */
    private $catalogRule;

    /**
     * @var TimezoneInterface
     */
    private $dateTime;

    /**
     * @var array
     */
    private $data;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Banner\Model\ResourceModel\Banner $bannerResource
     * @param \Magento\Banner\Model\Banner $banner
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\CatalogRule\Model\ResourceModel\Rule $catalogRule
     * @param TimezoneInterface $dateTime
     * @param array $data
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Banner\Model\ResourceModel\Banner $bannerResource,
        \Magento\Banner\Model\Banner $banner,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\CatalogRule\Model\ResourceModel\Rule $catalogRule,
        TimezoneInterface $dateTime,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->bannerResource = $bannerResource;
        $this->banner = $banner;
        $this->storeManager = $storeManager;
        $this->httpContext = $httpContext;
        $this->filterProvider = $filterProvider;
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->catalogRule = $catalogRule;
        $this->dateTime = $dateTime;
        $this->data = $data;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getSectionData()
    {
        return [
            'items' => [
                Config::BANNER_WIDGET_DISPLAY_SALESRULE => $this->getSalesRuleRelatedBanners(),
                Config::BANNER_WIDGET_DISPLAY_CATALOGRULE => $this->getCatalogRuleRelatedBanners(),
                Config::BANNER_WIDGET_DISPLAY_FIXED => $this->getFixedBanners(),
            ],
            'store_id' => $this->storeId
        ];
    }

    /**
     * Returns data for cart rule related banners applicable for the current session
     *
     * @return array
     */
    protected function getSalesRuleRelatedBanners()
    {
        return $this->getBannersData($this->getBannerIdsBySalesRules());
    }

    /**
     * Returns data for catalog rule related banners applicable for the current session
     *
     * @return array
     */
    protected function getCatalogRuleRelatedBanners()
    {
        return $this->getBannersData($this->getBannerIdsByCatalogRules());
    }

    /**
     * Returns data for active banners applicable for the current session
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    protected function getFixedBanners()
    {
        //add here check to load only active banners without assigned catalog rule and sales rule
        $bannersWithoutAssignedRules = $this->getActiveBannerIdsWithoutRelatedPromotions();

        $promotionsRelatedRules = array_merge_recursive(
            $this->getBannerIdsByCatalogRules(),
            $this->getBannerIdsBySalesRules()
        );
        $fixedBanners = array_merge_recursive($bannersWithoutAssignedRules, $promotionsRelatedRules);
        //merge here data from related catalogRules and related sales rules
        return $this->getBannersData($fixedBanners);
    }

    /**
     * Get real existing active banner ids which doesn't have assigned rules
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    private function getActiveBannerIdsWithoutRelatedPromotions()
    {
        $connection = $this->bannerResource->getConnection();
        $subSelect1 = $connection->select()->from(
            $this->bannerResource->getTable('magento_banner_catalogrule'),
            ['banner_id']
        );
        $subSelect2 = $connection->select()->from(
            $this->bannerResource->getTable('magento_banner_salesrule'),
            ['banner_id']
        );
        $sql = $this->bannerResource->getConnection()->select()->union(
            [
                $subSelect1,
                $subSelect2
            ],
            \Magento\Framework\DB\Select::SQL_UNION_ALL
        );
        $select = $connection->select()->from(
            $this->bannerResource->getMainTable(),
            ['banner_id']
        )->where(
            'is_enabled  = ?',
            1
        )->where(
            'banner_id not in (?)',
            $sql
        );

        return $connection->fetchCol($select);
    }

    /**
     * Get banners that associated to catalog rules
     *
     * @param array $ruleIds
     * @return array
     */
    private function getCatalogRuleRelatedBannerIds(array $ruleIds): array
    {
        $connection = $this->bannerResource->getConnection();
        $select = $connection->select()->from(
            $this->bannerResource->getTable('magento_banner_catalogrule'),
            ['banner_id']
        )->where(
            'rule_id IN (?)',
            $ruleIds
        );
        return $connection->fetchCol($select);
    }

    /**
     * Get banners IDs that related to sales rule and satisfy conditions
     *
     * @return array
     */
    private function getBannerIdsBySalesRules()
    {
        if ($this->bannersBySalesRule === null) {
            $appliedRules = [];
            if ($this->checkoutSession->getQuoteId()) {
                $quote = $this->checkoutSession->getQuote();
                if ($quote && $quote->getAppliedRuleIds()) {
                    $appliedRules = explode(',', $quote->getAppliedRuleIds());
                }
            }
            $this->bannersBySalesRule = $this->bannerResource->getSalesRuleRelatedBannerIds($appliedRules);
        }
        return $this->bannersBySalesRule;
    }

    /**
     * Get banners IDs that related to catalog rule and satisfy conditions
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getBannerIdsByCatalogRules()
    {
        if ($this->bannersByCatalogRule === null) {
            $productId =  !empty($this->data['product_id']) ? $this->data['product_id'] : null;
            $this->bannersByCatalogRule = $this->bannerResource->getCatalogRuleRelatedBannerIds(
                $this->storeManager->getWebsite()->getId(),
                $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP)
            );
            if ($productId) {
                $bannerCatalogRuleIds = $this->getProductRelatedBannerIds($productId);
                $this->bannersByCatalogRule = array_intersect($bannerCatalogRuleIds, $this->bannersByCatalogRule);
            }
        }

        return $this->bannersByCatalogRule;
    }

    /**
     * Get product related banner ids.
     *
     * @param int $productId
     * @return array
     */
    private function getProductRelatedBannerIds(int $productId): array
    {
        $result = $this->catalogRule->getRulesFromProduct(
            $this->dateTime->scopeDate($this->storeManager->getStore()->getId()),
            $this->storeManager->getWebsite()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
            $productId
        );

        $ruleIds = count($result) ? array_column($result, 'rule_id'): [];

        return $ruleIds ? $this->getCatalogRuleRelatedBannerIds($ruleIds) : [];
    }

    /**
     * Returns banner data by identifier
     *
     * @param array $bannersIds
     * @return array
     * @throws \Exception
     */
    protected function getBannersData($bannersIds)
    {
        $banners = [];
        foreach ($bannersIds as $bannerId) {
            if (!isset($this->banners[$bannerId])) {
                $content = $this->bannerResource->getStoreContent($bannerId, $this->storeId);
                if (!empty($content)) {
                    $this->banners[$bannerId] = [
                        'content' => $this->filterProvider->getPageFilter()->filter($content),
                        'types' => $this->banner->load($bannerId)->getTypes(),
                        'id' => $bannerId,
                    ];
                } else {
                    $this->banners[$bannerId] = null;
                }
            }
            $banners[$bannerId] = $this->banners[$bannerId];
        }
        return array_filter($banners);
    }
}
