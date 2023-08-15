<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Block;

use Magento\Catalog\Model\Category;
use Magento\Framework\App\ObjectManager;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;
use Magento\GoogleTagManager\Helper\Data as GoogleTagManagerHelper;

/**
 * Model list provider in JSON format.
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class ListJson extends \Magento\Framework\View\Element\Template
{
    /**
     * Catalog Product collection
     *
     * @var \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection
     */
    protected $_productCollection;

    /**
     * Variable is used to turn on/off the output of _getProductCollection for cross-sells
     *
     * @var bool
     */
    protected $_showCrossSells = true;

    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $checkoutCart;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    protected $catalogLayer;

    /**
     * Keeps collection of banners with GA related data included
     *
     * @var null|\Magento\Banner\Model\ResourceModel\Banner\Collection
     */
    protected $_bannerCollection = null;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory
     */
    protected $bannerColFactory;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\GoogleTagManager\Model\Banner\Collector
     */
    protected $bannerCollector;

    /**
     * @var TagManagerConfig
     */
    private $tagManagerConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Helper\Cart $checkoutCart
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory
     * @param \Magento\GoogleTagManager\Model\Banner\Collector $bannerCollector
     * @param array $data
     * @param TagManagerConfig|null $tagManagerConfig
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Cart $checkoutCart,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory,
        \Magento\GoogleTagManager\Model\Banner\Collector $bannerCollector,
        array $data = [],
        TagManagerConfig $tagManagerConfig = null
    ) {
        $this->helper = $helper;
        $this->jsonHelper = $jsonHelper;
        $this->registry = $registry;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->checkoutCart = $checkoutCart;
        $this->catalogLayer = $layerResolver->get();
        $this->moduleManager = $moduleManager;
        $this->bannerColFactory = $bannerColFactory;
        $this->request = $request;
        $this->bannerCollector = $bannerCollector;
        $this->tagManagerConfig = $tagManagerConfig ?? ObjectManager::getInstance()->get(
            TagManagerConfig::class
        );
        parent::__construct($context, $data);
    }

    /**
     * Render GA tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Returns an instance of an assigned block via a layout update file
     *
     * @return \Magento\Framework\View\Element\Template
     */
    public function getListBlock()
    {
        return $this->getLayout()->getBlock($this->getBlockName());
    }

    /**
     * Set a variable to false to hide cross-sell items for an empty cart
     *
     * @return void
     */
    public function checkCartItems()
    {
        if (!$this->checkoutCart->getItemsCount()) {
            $this->_showCrossSells = false;
        }
    }

    /**
     * Retrieve loaded category collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection | null
     */
    protected function _getProducts()
    {
        /** @var Category $category */
        $category = $this->getCurrentCategory();

        if ($category
            && (
                $category->getDisplayMode() === null
                || in_array($category->getDisplayMode(), [Category::DM_MIXED, Category::DM_PRODUCT], true)
            )
        ) {
            return $this->_getProductCollection();
        }
        return null;
    }

    /**
     * Retrieve loaded category collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection | null
     */
    protected function _getProductCollection()
    {
        if (!$this->getListBlock()) {
            return null;
        }

        /* For catalog list and search results
         * Expects getListBlock as \Magento\Catalog\Block\Product\ListProduct
         */
        if ($this->_productCollection === null) {
            $this->_productCollection = $this->getListBlock()->getLoadedProductCollection();
        }

        /* For collections of cross/up-sells and related
         * Expects getListBlock as one of the following:
         * \Magento\TargetRule\Block\Catalog\Product\ProductList\Upsell | _linkCollection
         * \Magento\TargetRule\Block\Catalog\Product\ProductList\Related | _items
         * \Magento\TargetRule\Block\Checkout\Cart\Crosssell | _items
         * \Magento\Catalog\Block\Product\ProductList\Related | _itemCollection
         * \Magento\Catalog\Block\Product\ProductList\Upsell | _itemCollection
         * \Magento\Checkout\Block\Cart\Crosssell, | setter items
         */
        if ($this->_showCrossSells && (null === $this->_productCollection)) {
            $this->_productCollection = $this->getListBlock()->getItemCollection();
        }

        // Support for CE
        if ((null === $this->_productCollection)
            && ($this->getBlockName() == 'catalog.product.related'
                || $this->getBlockName() == 'checkout.cart.crosssell')
        ) {
            $this->_productCollection = $this->getListBlock()->getItems();
        }

        return $this->_productCollection;
    }

    /**
     * Retrieve loaded category collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection | null
     */
    public function getLoadedProductCollection()
    {
        return $this->_getProducts();
    }

    /**
     * Retrieves a current category
     *
     * @return Category
     */
    public function getCurrentCategory()
    {
        /** @var Category $category */
        $category = null;
        if ($this->catalogLayer) {
            $category = $this->catalogLayer->getCurrentCategory();
        } elseif ($this->registry->registry('current_category')) {
            $category = $this->registry->registry('current_category');
        }
        return $category;
    }

    /**
     * Returns current product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('product');
    }

    /**
     * Retrieves name of the current category
     *
     * @return string
     */
    public function getCurrentCategoryName()
    {
        if (!$this->getShowCategory()) {
            return '';
        }
        /** @var Category $category */
        $category = $this->getCurrentCategory();

        if ($category && $this->_storeManager->getStore()->getRootCategoryId() != $category->getId()) {
            return $category->getName();
        }
        return '';
    }

    /**
     * Retrieves name of the current list assigned via layout update
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getCurrentListName()
    {
        $listName = '';
        if (strlen($this->getListType())) {
            switch ($this->getListType()) {
                case 'catalog':
                    $listName = $this->tagManagerConfig->isTagManagerAvailable() ?
                        $this->_scopeConfig->getValue(
                            TagManagerConfig::XML_PATH_LIST_CATALOG_PAGE
                        ) : $this->_scopeConfig->getValue(
                            GoogleTagManagerHelper::XML_PATH_LIST_CATALOG_PAGE
                        );
                    break;
                case 'search':
                    $listName = $this->tagManagerConfig->isTagManagerAvailable() ?
                        $this->_scopeConfig->getValue(
                            TagManagerConfig::XML_PATH_LIST_SEARCH_PAGE
                        ) : $this->_scopeConfig->getValue(
                            GoogleTagManagerHelper::XML_PATH_LIST_SEARCH_PAGE
                        );
                    break;
                case 'related':
                    $listName = $this->tagManagerConfig->isTagManagerAvailable() ?
                        $this->_scopeConfig->getValue(
                            TagManagerConfig::XML_PATH_LIST_RELATED_BLOCK
                        ) : $this->_scopeConfig->getValue(
                            GoogleTagManagerHelper::XML_PATH_LIST_RELATED_BLOCK
                        );
                    break;
                case 'upsell':
                    $listName = $this->tagManagerConfig->isTagManagerAvailable() ?
                        $this->_scopeConfig->getValue(
                            TagManagerConfig::XML_PATH_LIST_UPSELL_BLOCK
                        ) : $this->_scopeConfig->getValue(
                            GoogleTagManagerHelper::XML_PATH_LIST_UPSELL_BLOCK
                        );
                    break;
                case 'crosssell':
                    $listName = $this->tagManagerConfig->isTagManagerAvailable() ?
                        $this->_scopeConfig->getValue(
                            TagManagerConfig::XML_PATH_LIST_CROSSSELL_BLOCK
                        ) : $this->_scopeConfig->getValue(
                            GoogleTagManagerHelper::XML_PATH_LIST_CROSSSELL_BLOCK
                        );
                    break;
            }
        }
        return $listName;
    }

    /**
     * Add content to the banner block
     *
     * @param \Magento\Banner\Block\Widget\Banner $block
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function appendBannerBlock($block)
    {
        return $this;
    }

    /**
     * Returns a collection of banners by rendered ids
     *
     * @return null|\Magento\Banner\Model\ResourceModel\Banner\Collection
     */
    public function getBannerCollection()
    {
        if ($this->_bannerCollection != null) {
            return $this->_bannerCollection;
        }
        $bannerIds = $this->bannerCollector->getBannerIds();
        if (count($bannerIds)) {
            $this->_bannerCollection = $this->bannerColFactory->create()
                ->addBannerIdsFilter($bannerIds);
        }
        return $this->_bannerCollection;
    }

    /**
     * Returns banner position defined by SPEC as Current page (controller handler)
     *
     * @return string
     */
    public function getBannerPosition()
    {
        $actionName = $this->request->getFullActionName();
        return empty($actionName) ? '' : $actionName;
    }

    /**
     * Mapping of checkout steps to numbers for both simple and multishipping checkout
     *
     * @return int
     */
    protected function getStepNumber()
    {
        $steps = [
            'login'     => 1,

            'billing'   => 2,
            'shipping'  => 3,
            'shipping_method' => 4,
            'payment'   => 5,
            'review'    => 6,

            'addresses' => 2,
            'multishipping' => 3,
            'multibilling'  => 4,
            'multireview' => 5
        ];

        /** stepName is set in layout file */
        if ($this->getStepName() && array_key_exists($this->getStepName(), $steps)) {
            return $steps[$this->getStepName()];
        }
        return 0;
    }

    /**
     * Detect step name
     *
     * @return void
     */
    public function detectStepName()
    {
        $stepName = $this->isCustomerLoggedIn() ? 'billing' : 'login';
        $this->setStepName($stepName);
    }

    /**
     * Check if customer is logged in.
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Generates json array of all products in the cart for javascript on each checkout step
     *
     * @return string
     */
    public function getCartContent()
    {
        $cart = [];
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->getCheckoutSession()->getQuote();
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            $cart[]= $this->_formatProduct($item);
        }
        return $this->jsonHelper->jsonEncode($cart);
    }

    /**
     * Generates json array of all products in the cart for javascript on each checkout step
     *
     * @return string
     */
    public function getCartContentForUpdate()
    {
        $cart = [];
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->getCheckoutSession()->getQuote();
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            $cart[$item->getSku()]= $this->_formatProduct($item);
        }
        return $this->jsonHelper->jsonEncode($cart);
    }

    /**
     * Format product item for output to json
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return array
     */
    protected function _formatProduct($item)
    {
        $product = [];
        $product['id'] = $item->getSku();
        $product['name'] = $item->getName();
        $product['price'] = $item->getPrice();
        $product['qty'] = $item->getQty();
        return $product;
    }

    /**
     * Returns checkout session object.
     *
     * @return \Magento\Checkout\Model\Session
     * @throws \Magento\Framework\Exception\SessionException
     */
    private function getCheckoutSession()
    {
        if (!$this->checkoutSession->isSessionExists()) {
            $this->checkoutSession->start();
        }
        return $this->checkoutSession;
    }
}
