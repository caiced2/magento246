<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Controller\Index;

/**
 * Website stub controller
 */
class Stub extends \Magento\Framework\App\Action\Action
{
    /**
     * @var string
     */
    protected $_stubPageIdentifier = \Magento\WebsiteRestriction\Model\Config::XML_PATH_RESTRICTION_LANDING_PAGE;

    /**
     * @var \Magento\Framework\App\Cache\Type\Config
     */
    protected $_configCacheType;

    /**
     * @var string
     */
    protected $_cacheKey;

    /**
     * Prefix for cache id
     *
     * @var string
     */
    protected $_cacheKeyPrefix = 'RESTRICTION_LANGING_PAGE_';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Store\Model\Website
     */
    protected $_website;

    /**
     * @var \Magento\Cms\Model\PageFactory
     */
    protected $_pageFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Store\Model\Website $website
     * @param \Magento\Cms\Model\PageFactory $pageFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Store\Model\Website $website,
        \Magento\Cms\Model\PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_configCacheType = $configCacheType;
        $this->_website = $website;
        $this->_pageFactory = $pageFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_localeDate = $localeDate;
        parent::__construct($context);
        $this->_cacheKey = $this->_cacheKeyPrefix . $this->_website->getId();
    }

    /**
     * Display a pre-cached CMS-page if we have such or generate new one
     *
     * @return void
     */
    public function execute()
    {
        $cachedData = $this->_configCacheType->load($this->_cacheKey);
        if ($cachedData) {
            $this->getResponse()->setBody($cachedData);
        } else {
            /**
             * Generating page and save it to cache
             */
            /** @var \Magento\Cms\Model\Page $page */
            $page = $this->_pageFactory->create()->load(
                $this->_scopeConfig->getValue(
                    $this->_stubPageIdentifier,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'identifier'
            );

            $this->_coreRegistry->register('restriction_landing_page', $page);

            if ($page->getCustomTheme()) {
                if ($this->_localeDate->isScopeDateInInterval(
                    null,
                    $page->getCustomThemeFrom(),
                    $page->getCustomThemeTo()
                )
                ) {
                    $this->_objectManager->get(
                        \Magento\Framework\View\DesignInterface::class
                    )->setDesignTheme(
                        $page->getCustomTheme()
                    );
                }
            }

            $this->_view->addActionLayoutHandles();
            if ($page->getPageLayout()) {
                /** @var \Magento\Framework\View\Page\Config $pageConfig */
                $pageConfig = $this->_objectManager->get(\Magento\Framework\View\Page\Config::class);
                $pageConfig->setPageLayout($page->getPageLayout());
            }

            $this->_view->loadLayout($page->getLayoutUpdateXml());

            $this->_view->renderLayout();

            $this->_configCacheType->save(
                $this->getResponse()->getBody(),
                $this->_cacheKey,
                [\Magento\Store\Model\Website::CACHE_TAG]
            );
        }
    }
}
