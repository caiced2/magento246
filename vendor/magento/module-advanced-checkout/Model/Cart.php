<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\AdvancedCheckout\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Validator\FloatUtils;
use Magento\Framework\Validator\ValidatorChain;
use Magento\Quote\Model\Quote;

/**
 * Admin Checkout processing model
 *
 * @api
 * @method bool hasErrorMessage()
 * @method string getErrorMessage()
 * @method setErrorMessage(string $message)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @since 100.0.2
 */
class Cart extends \Magento\Framework\DataObject implements \Magento\Checkout\Model\Cart\CartInterface
{
    /**
     * Context of the cart - admin order
     */
    public const CONTEXT_ADMIN_ORDER = 'admin_order';

    /**
     * Context of the cart - admin checkout
     */
    public const CONTEXT_ADMIN_CHECKOUT = 'admin_checkout';

    /**
     * Context of the cart - frontend
     */
    public const CONTEXT_FRONTEND = 'frontend';

    /**
     * Context of the cart
     *
     * @var string
     */
    protected $_context;

    /**
     * Quote instance
     *
     * @var \Magento\Quote\Model\Quote|null
     */
    protected $_quote;

    /**
     * Customer model instance
     *
     * @var \Magento\Customer\Model\Customer|null
     */
    protected $_customer;

    /**
     * List of currently affected items skus
     *
     * @var string[]
     */
    protected $_currentlyAffectedItems = [];

    /**
     * Configs of currently affected items
     *
     * @var array
     */
    protected $_affectedItemsConfig = [];

    /**
     * Cart instance
     *
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * Product options for configuring
     *
     * @var array
     */
    protected $_successOptions = [];

    /**
     * Instance of current store
     *
     * @var null|\Magento\Store\Model\Store
     */
    protected $_currentStore;

    /**
     * @var \Magento\AdvancedCheckout\Helper\Data
     */
    protected $_checkoutData;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\Framework\Message\Factory
     */
    protected $messageFactory;

    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Wishlist factory model
     *
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    protected $_wishlistFactory;

    /**
     * Catalog product option factory
     *
     * @var \Magento\Catalog\Model\Product\OptionFactory
     */
    protected $_optionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $_localeFormat;

    /**
     * @var string
     */
    protected $_itemFailedStatus;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $productTypeConfig;

    /**
     * @var \Magento\Catalog\Model\Product\CartConfiguration
     */
    protected $productConfiguration;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockState;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockHelper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private $products = [];

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private $productsConfig = [];

    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var IsProductInStockInterface
     */
    private $isProductInStock;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Message\Factory $messageFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\AdvancedCheckout\Helper\Data $checkoutData
     * @param \Magento\Catalog\Model\Product\OptionFactory $optionFactory
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Catalog\Model\Product\CartConfiguration $productConfiguration
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param string $itemFailedStatus
     * @param array $data
     * @param Json $serializer
     * @param \Magento\Framework\Api\SearchCriteriaBuilder|null $searchCriteriaBuilder
     * @param IsProductInStockInterface|null $isProductInStock
     * @param AreProductsSalableForRequestedQtyInterface|null $areProductsSalableForRequestedQty
     * @param CollectionFactory|null $productCollectionFactory
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Message\Factory $messageFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Data $checkoutData,
        \Magento\Catalog\Model\Product\OptionFactory $optionFactory,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Catalog\Model\Product\CartConfiguration $productConfiguration,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        $itemFailedStatus = Data::ADD_ITEM_STATUS_FAILED_SKU,
        array $data = [],
        Json $serializer = null,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder = null,
        IsProductInStockInterface $isProductInStock = null,
        AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty = null,
        CollectionFactory $productCollectionFactory = null
    ) {
        $this->_cart = $cart;
        $this->messageFactory = $messageFactory;
        $this->_eventManager = $eventManager;
        $this->_checkoutData = $checkoutData;
        $this->_optionFactory = $optionFactory;
        $this->_wishlistFactory = $wishlistFactory;
        $this->quoteRepository = $quoteRepository;
        $this->_storeManager = $storeManager;
        $this->_localeFormat = $localeFormat;
        $this->_itemFailedStatus = $itemFailedStatus;
        $this->messageManager = $messageManager;
        $this->productTypeConfig = $productTypeConfig;
        $this->productConfiguration = $productConfiguration;
        $this->customerSession = $customerSession;
        $this->stockRegistry = $stockRegistry;
        $this->stockState = $stockState;
        $this->stockHelper = $stockHelper;
        $this->productRepository = $productRepository;
        $this->quoteFactory = $quoteFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder
            ?: ObjectManager::getInstance()->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->isProductInStock = $isProductInStock
            ?: ObjectManager::getInstance()->get(IsProductInStockInterface::class);
        $this->areProductsSalableForRequestedQty = $areProductsSalableForRequestedQty
            ?? ObjectManager::getInstance()->get(AreProductsSalableForRequestedQtyInterface::class);
        $this->productCollectionFactory = $productCollectionFactory
            ?? ObjectManager::getInstance()->get(CollectionFactory::class);
        parent::__construct($data);
    }

    /**
     * Set context of the cart
     *
     * @codeCoverageIgnore
     * @param string $context
     * @return $this
     */
    public function setContext($context)
    {
        $this->_context = $context;
        return $this;
    }

    /**
     * Setter for $_customer
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @return $this
     */
    public function setCustomer($customer)
    {
        if ($customer instanceof \Magento\Framework\DataObject && $customer->getId()) {
            $this->_customer = $customer;
            $this->_quote = null;
        }
        return $this;
    }

    /**
     * Getter for $_customer
     *
     * @codeCoverageIgnore
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        return $this->_customer;
    }

    /**
     * Return quote store
     *
     * @codeCoverageIgnore
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->getQuote()->getStore();
    }

    /**
     * Return current active quote for specified customer
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        if ($this->_quote !== null) {
            return $this->_quote;
        }

        $this->_quote = $this->quoteFactory->create();

        if ($this->getCustomer() !== null) {
            try {
                $this->_quote = $this->quoteRepository->getForCustomer(
                    $this->getCustomer()->getId(),
                    $this->getQuoteSharedStoreIds()
                );
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            }
        }

        return $this->_quote;
    }

    /**
     * Sets different quote model
     *
     * @codeCoverageIgnore
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this
     */
    public function setQuote(\Magento\Quote\Model\Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }

    /**
     * Return quote instance
     *
     * @return \Magento\Backend\Model\Session\Quote|\Magento\Quote\Model\Quote
     */
    public function getActualQuote()
    {
        if (!$this->getCustomer()) {
            $customer = $this->customerSession->getCustomer();
            if ($customer) {
                $this->setCustomer($customer);
            }
        }
        return $this->getQuote();
    }

    /**
     * Return appropriate store ids for retrieving quote in current store.
     *
     * Correct customer shared store ids when customer has Admin Store.
     *
     * @return int[]
     */
    public function getQuoteSharedStoreIds()
    {
        if ($this->getStoreId()) {
            return $this->_storeManager->getStore($this->getStoreId())->getWebsite()->getStoreIds();
        }
        if (!$this->getCustomer()) {
            return [];
        }
        if ((bool)$this->getCustomer()->getSharingConfig()->isWebsiteScope()) {
            return $this->_storeManager->getWebsite($this->getCustomer()->getWebsiteId())->getStoreIds();
        } else {
            return $this->getCustomer()->getSharedStoreIds();
        }
    }

    /**
     * Create quote by demand or return active customer quote if it exists
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function createQuote()
    {
        if (!$this->getQuote()->getId() && $this->getCustomer() !== null) {
            $this->quoteRepository->save($this->getQuote()->assignCustomer($this->getCustomer()));
        }
        return $this->getQuote();
    }

    /**
     * Recollect quote and save it
     *
     * @param bool $recollect Collect quote totals or not
     * @return $this
     */
    public function saveQuote($recollect = true)
    {
        if (!$this->getQuote()->getId()) {
            return $this;
        }
        if ($recollect) {
            $this->getQuote()->collectTotals();
        }
        $this->quoteRepository->save($this->getQuote());
        return $this;
    }

    /**
     * Return preferred non-admin store Id
     *
     * If Customer has active quote - return its store, otherwise try to get customer store or default store
     *
     * @return int|bool
     */
    public function getPreferredStoreId()
    {
        $quote = $this->getQuote();
        $customer = $this->getCustomer();
        $defaultStoreId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        if ($quote->getId() && $quote->getStoreId()) {
            $storeId = $quote->getStoreId();
        } elseif ($customer !== null && $customer->getStoreId() && $customer->getStoreId() != $defaultStoreId) {
            $storeId = $customer->getStoreId();
        } else {
            $customerStoreIds = $this->getQuoteSharedStoreIds();
            $storeId = array_shift($customerStoreIds);
            if ($storeId != $defaultStoreId) {
                $defaultStore = $this->_storeManager->getDefaultStoreView();
                if ($defaultStore) {
                    $storeId = $defaultStore->getId();
                }
            }
        }

        return $storeId;
    }

    /**
     * Add product to current order quote
     *
     * Parameter $config can be integer qty (older behaviour, when no product configuration was possible)
     * or it can be array of options (newer behaviour).
     *
     * In case of older behaviour same product ids are not added, but quote item qty is increased.
     * In case of newer behaviour same product ids with different configs are added as separate quote items.
     *
     * @param   Product|int $product
     * @param   array|float|int|\Magento\Framework\DataObject $config
     * @return  $this
     * @throws  \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addProduct($product, $config = 1)
    {
        if (is_array($config) || $config instanceof \Magento\Framework\DataObject) {
            $config = is_array($config) ? new \Magento\Framework\DataObject($config) : $config;
            $qty = (float)$config->getQty();
            $separateSameProducts = true;
        } else {
            $qty = (float)$config;
            $config = new \Magento\Framework\DataObject();
            $config->setQty($qty);
            $separateSameProducts = false;
        }

        if (!$product instanceof Product) {
            $productId = $product;
            try {
                $product = $this->productRepository->getById($productId, false, $this->getStore()->getId(), true);
            } catch (NoSuchEntityException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We can\'t add a product to cart by id "%1".', $productId),
                    $e
                );
            }
        }

        $stockItemDo = $this->stockRegistry->getStockItem($product->getId(), $this->getStore()->getWebsiteId());
        if ($stockItemDo->getItemId()) {
            if (!$stockItemDo->getIsQtyDecimal()) {
                $qty = (int)$qty;
            } else {
                $product->setIsQtyDecimal(1);
            }
        }
        $qty = $qty > 0 ? $qty : 1;

        $item = null;
        if (!$separateSameProducts) {
            $item = $this->getQuote()->getItemByProduct($product);
        }
        if ($item) {
            $item->setQty($item->getQty() + $qty);
        } else {
            $item = $this->getQuote()->addProduct($product, $config);
            if (is_string($item)) {
                throw new \Magento\Framework\Exception\LocalizedException(__($item));
            }
            $item->checkData();
        }

        $this->setRecollect(true);
        return $this;
    }

    /**
     * Add new item to quote based on existing order Item
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param int|float $qty
     * @return \Magento\Quote\Model\Quote\Item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function reorderItem(\Magento\Sales\Model\Order\Item $orderItem, $qty = 1)
    {
        if (!$orderItem->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while reordering this product.')
            );
        }

        try {
            $product = $this->productRepository->getById($orderItem->getProductId(), false, $this->getStore()->getId());
        } catch (NoSuchEntityException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while reordering this product.'),
                $e
            );
        }
        $info = $orderItem->getProductOptionByCode('info_buyRequest');
        $info = new \Magento\Framework\DataObject($info);
        $product->setSkipCheckRequiredOption(true);
        $item = $this->createQuote()->addProduct($product, $info);
        if (is_string($item)) {
            throw new \Magento\Framework\Exception\LocalizedException(__($item));
        }

        $item->setQty($qty);

        if ($additionalOptions = $orderItem->getProductOptionByCode('additional_options')) {
            $item->addOption(
                new \Magento\Framework\DataObject(
                    [
                        'product' => $item->getProduct(),
                        'code' => 'additional_options',
                        'value' => $this->serializer->serialize($additionalOptions),
                    ]
                )
            );
        }

        $this->_eventManager->dispatch(
            'sales_convert_order_item_to_quote_item',
            ['order_item' => $orderItem, 'quote_item' => $item]
        );

        return $item;
    }

    /**
     * Add multiple products to current order quote.
     *
     * Errors can be received via getResultErrors() or directly into session if it was set via setSession().
     *
     * @param   array $products
     * @return  $this|\Exception
     */
    public function addProducts(array $products)
    {
        foreach ($products as $productId => $config) {
            $config['qty'] = isset($config['qty']) ? (float)$config['qty'] : 1;
            try {
                $this->addProduct($productId, $config);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                return $e;
            }
        }

        return $this;
    }

    /**
     * Remove items from quote or move them to wishlist etc.
     *
     * @param array $data Array of items
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function updateQuoteItems($data)
    {
        if (!$this->getQuote()->getId() || !is_array($data)) {
            return $this;
        }

        foreach ($data as $itemId => $info) {
            if (!empty($info['configured'])) {
                $item = $this->getQuote()->updateItem($itemId, new \Magento\Framework\DataObject($info));
                $itemQty = (float)$item->getQty();
            } else {
                $item = $this->getQuote()->getItemById($itemId);
                $itemQty = (float)$info['qty'];
            }

            if ($item) {
                $stockItemDo = $this->stockRegistry->getStockItem(
                    $item->getProduct()->getId(),
                    $this->getStore()->getWebsiteId()
                );
                if ($stockItemDo->getItemId() && !$stockItemDo->getIsQtyDecimal()) {
                    $itemQty = (int)$itemQty;
                } else {
                    $item->setIsQtyDecimal(1);
                }
            }

            $itemQty = $itemQty > 0 ? $itemQty : 1;
            if (isset($info['custom_price'])) {
                // Looks like this line of code could not be reached because there is no _parseCustomPrice() method
                // from the time this file was created in 2014
                // Just commented it out since phpStan throw warning for it
                //$itemPrice = $this->_parseCustomPrice($info['custom_price']);
                $itemPrice = null;
            } else {
                $itemPrice = null;
            }

            if (empty($info['action']) || !empty($info['configured'])) {
                if ($item) {
                    $item->setQty($itemQty);
                    $item->setCustomPrice($itemPrice);
                    $item->setOriginalCustomPrice($itemPrice);
                    $item->getProduct()->setIsSuperMode(true);
                    $item->checkData();
                }
            } else {
                $this->moveQuoteItem($item->getId(), $info['action']);
            }
        }
        $this->setRecollect(true);

        return $this;
    }

    /**
     * Move quote item to wishlist.
     *
     * Errors can be received via getResultErrors() or directly into session if it was set via setSession().
     *
     * @param \Magento\Quote\Model\Quote\Item|int $item
     * @param string $moveTo Destination storage
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function moveQuoteItem($item, $moveTo)
    {
        $item = $this->_getQuoteItem($item);
        if ($item) {
            $moveTo = explode('_', $moveTo);
            if ($moveTo[0] == 'wishlist') {
                $wishlist = null;
                if (!isset($moveTo[1])) {
                    $wishlist = $this->_wishlistFactory->create()
                        ->loadByCustomerId($this->getCustomer()->getId(), true);
                } else {
                    $wishlist = $this->_wishlistFactory->create()->load($moveTo[1]);
                    if (!$wishlist->getId() || $wishlist->getCustomerId() != $this->getCustomer()->getId()) {
                        $wishlist = null;
                    }
                }
                if (!$wishlist) {
                    $this->messageManager->addError(__('We can\'t find this wish list.'));
                    return $this;
                }
                $wishlist->setStore($this->getStore())
                    ->setSharedStoreIds($this->getStore()->getWebsite()->getStoreIds());
                if ($wishlist->getId() && $item->getProduct()->isVisibleInSiteVisibility()) {
                    $wishlistItem = $wishlist->addNewItem($item->getProduct(), $item->getBuyRequest());
                    if (is_string($wishlistItem)) {
                        $this->messageManager->addError($wishlistItem);
                    } elseif ($wishlistItem->getId()) {
                        $this->getQuote()->removeItem($item->getId());
                    }
                }
            } else {
                $this->getQuote()->removeItem($item->getId());
            }
        }
        return $this;
    }

    /**
     * Create duplicate of quote preserving all data (items, addresses, payment etc.)
     *
     * @param \Magento\Quote\Model\Quote $quote Original Quote
     * @param bool $active Create active quote or not
     * @return \Magento\Quote\Model\Quote New created quote
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function copyQuote(\Magento\Quote\Model\Quote $quote, $active = false)
    {
        if (!$quote->getId()) {
            return $quote;
        }
        $newQuote = clone $quote;
        $newQuote->setId(null);
        $newQuote->setIsActive($active ? 1 : 0);
        $this->quoteRepository->save($newQuote);

        // copy items with their options
        $newParentItemIds = [];
        foreach ($quote->getItemsCollection() as $item) {
            // save child items later
            if ($item->getParentItem()) {
                continue;
            }
            $oldItemId = $item->getId();
            $newItem = clone $item;
            $newItem->setQuote($newQuote);
            $newItem->save();
            $newParentItemIds[$oldItemId] = $newItem->getId();
        }

        // save children with new parent id
        foreach ($quote->getItemsCollection() as $item) {
            if (!$item->getParentItem() || !isset($newParentItemIds[$item->getParentItemId()])) {
                continue;
            }
            $newItem = clone $item;
            $newItem->setQuote($newQuote);
            $newItem->setParentItemId($newParentItemIds[$item->getParentItemId()]);
            $newItem->save();
        }

        // copy billing and shipping addresses
        foreach ($quote->getAddressesCollection() as $address) {
            $address->setQuote($newQuote);
            $address->setId(null);
            $address->save();
        }

        // copy payment info
        foreach ($quote->getPaymentsCollection() as $payment) {
            $payment->setQuote($newQuote);
            $payment->setId(null);
            $payment->save();
        }

        return $newQuote;
    }

    /**
     * Wrapper for getting quote item
     *
     * @param \Magento\Quote\Model\Quote\Item|int $item
     * @return \Magento\Quote\Model\Quote\Item|bool
     */
    protected function _getQuoteItem($item)
    {
        if ($item instanceof \Magento\Quote\Model\Quote\Item) {
            return $item;
        } elseif (is_numeric($item)) {
            return $this->getQuote()->getItemById($item);
        }
        return false;
    }

    /**
     * Update failed item quantities and add to cart
     *
     * @param array $failedItems
     * @param array $cartItems
     * @return void
     */
    public function updateFailedItems($failedItems, $cartItems)
    {
        foreach ($failedItems as $failedItem) {
            $qty = '';
            if (array_key_exists('sku', $failedItem)) {
                $sku = $failedItem['sku'];
                if (isset($cartItems[$sku]) && isset($cartItems[$sku]['qty'])) {
                    $qty = $cartItems[$sku]['qty'];
                }
                $this->prepareAddProductBySku($sku, $qty);
            }
        }
    }

    /**
     * Add single item to stack and return extended pushed item. For return format see _addAffectedItem()
     *
     * @param string $sku
     * @param float|string $qty
     * @param array $config Configuration data of the product (if has been configured)
     * @return array
     */
    public function prepareAddProductBySku($sku, $qty, $config = [])
    {
        $affectedItems = $this->getAffectedItems();

        if (isset($affectedItems[$sku])) {
            $qty = $this->getUpdatedAffectedItemQty($affectedItems[$sku], $qty);
            unset($affectedItems[$sku]);
            $this->setAffectedItems($affectedItems);
        }

        $checkedItem = $this->checkItem($sku, $qty, $config);
        $code = $checkedItem['code'];
        unset($checkedItem['code']);
        return $this->_addAffectedItem($checkedItem, $code);
    }

    /**
     * This condition made for case when user inputs same SKU in several rows. We need to update qty, otherwise
     * getQtyStatus() may return invalid result. If there's already such SKU in affected items array it means
     * that both came from add form (not from error grid as the case when there is several products with same
     * SKU requiring attention is not possible), so there could be no config.
     *
     * @param array $affectedItem
     * @param float|string $qty
     * @return float|string
     */
    private function getUpdatedAffectedItemQty(array $affectedItem, $qty)
    {
        return empty($qty) || empty($affectedItem['item']['qty'])
            ? ''
            : $qty + $affectedItem['item']['qty'];
    }

    /**
     * Check submitted SKUs
     *
     * @param array $items Example: [['sku' => 'simple1', 'qty' => 2], ['sku' => 'simple2', 'qty' => 3], ...]
     * @return $this
     *
     * @see saveAffectedProducts()
     */
    public function prepareAddProductsBySku(array $items)
    {
        $affectedItems = $this->getAffectedItems();
        foreach ($items as $n => &$item) {
            $item += ['sku' => '', 'qty' => ''];
            $sku = trim($item['sku']);
            $qty = $item['qty'];
            if (isset($affectedItems[$sku])) {
                $qty = $this->getUpdatedAffectedItemQty($affectedItems[$sku], $qty);
            }
            $item = $this->_getValidatedItem($sku, $qty);

            if ($item['code'] === Data::ADD_ITEM_STATUS_FAILED_EMPTY) {
                unset($items[$n]);
            }
        }

        foreach ($this->checkItems($items) as $item) {
            $code = $item['code'];
            unset($item['code']);
            $this->_addAffectedItem($item, $code);
        }

        return $this;
    }

    /**
     * Checks whether requested quantity is allowed taking into account that some amount already added to quote.
     * Returns TRUE if everything is okay
     * Returns array in below format on error:
     * [
     *  'status' => string (see \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_FAILED_* constants),
     *  'qty_max_allowed' => int (optional, if 'status'==ADD_ITEM_STATUS_FAILED_QTY_ALLOWED)
     * ]
     *
     * @param Product $product
     * @param float $requestedQty
     * @return array|true
     */
    public function getQtyStatus(
        Product $product,
        $requestedQty
    ) {
        $result = $this->stockState->checkQuoteItemQty(
            $product->getId(),
            $requestedQty,
            $requestedQty,
            $requestedQty,
            $this->getStore()->getWebsiteId()
        );
        if ($result->getHasError()) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId(), $this->getStore()->getWebsiteId());
            $return = [];
            switch ($result->getErrorCode()) {
                case 'qty_increments':
                case 'is_correct_qty-qty_increment':
                    $status = Data::ADD_ITEM_STATUS_FAILED_QTY_INCREMENTS;
                    $return['qty_increments'] = $stockItem->getQtyIncrements();
                    break;
                case 'qty_min':
                case 'is_correct_qty-min_sale_qty':
                    $status = Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART;
                    $return['qty_min_allowed'] = $stockItem->getMinSaleQty();
                    break;
                case 'qty_max':
                case 'is_correct_qty-max_sale_qty':
                    $status = Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART;
                    $return['qty_max_allowed'] = $stockItem->getMaxSaleQty();
                    break;
                default:
                    $status = Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED;
                    $return['qty_max_allowed'] = $this->stockState->getStockQty(
                        $product->getId(),
                        $this->getStore()->getWebsiteId()
                    );
                    break;
            }

            $return['status'] = $status;
            $return['error'] = $result->getMessage();
            return $return;
        }
        return true;
    }

    /**
     * Load product by specified sku
     *
     * @param string $sku
     * @return bool|Product
     */
    protected function _loadProductBySku($sku)
    {
        $storeId = $this->getCurrentStore()->getId();
        $product = $this->getProductFromLocalCache($sku, $storeId);
        if (null === $product) {
            try {
                $product = $this->productRepository->get($sku, false, $storeId);
                $this->addProductToLocalCache($product, $storeId);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                return false;
            }
        }
        return $product;
    }

    /**
     * Load product by specified sku for product with configuration.
     *
     * This method gets product by sku from the repository,
     * clones and saves it in the productConfig array with a key that is a sku and config hash.
     * Also, the method allows adding several configurations of the same product into a quote.
     * For a complex product, you must use a separate product object for each configuration.
     *
     * @param string $sku
     * @param array $config
     * @return bool|ProductInterface
     */
    private function loadProductBySkuWithConfig($sku, array $config)
    {
        $configSkuKey = $sku . $this->serializer->serialize($config);
        if (!isset($this->productsConfig[$configSkuKey])) {
            if (!isset($this->productsConfig[$sku])) {
                try {
                    $storeId = $this->getCurrentStore()->getId();
                    $this->productsConfig[$sku] = $this->getProductFromLocalCache($sku, $storeId)
                        ?? $this->productRepository->get($sku, false, $this->getCurrentStore()->getId());
                } catch (NoSuchEntityException $e) {
                    $this->productsConfig[$sku] = false;
                }
            }
            $this->productsConfig[$configSkuKey] = $this->productsConfig[$sku] === false
                ? false
                : clone $this->productsConfig[$sku];
        }

        return $this->productsConfig[$configSkuKey];
    }

    /**
     * Check whether required option is not missed, add values to configuration.
     *
     * @param array $skuParts
     * @param \Magento\Catalog\Model\Product\Option $option
     * @return bool
     */
    protected function _processProductOption(array &$skuParts, \Magento\Catalog\Model\Product\Option $option)
    {
        $missedRequired = true;
        $optionValues = $option->getValues();
        if (empty($optionValues)) {
            if ($option->hasSku()) {
                $found = array_search($option->getSku(), $skuParts);
                if ($found !== false) {
                    unset($skuParts[$found]);
                }
            }
            // we are not able to configure such option automatically
            return !$missedRequired;
        }

        foreach ($optionValues as $optionValue) {
            $found = array_search($optionValue->getSku(), $skuParts);
            if ($found !== false) {
                $this->_addSuccessOption($option, $optionValue);
                unset($skuParts[$found]);
                // we've found the value of required option
                $missedRequired = false;
                if (!$this->_isOptionMultiple($option)) {
                    break 1;
                }
            }
        }

        return !$missedRequired;
    }

    /**
     * Load product with its options by specified sku
     *
     * @param string $sku
     * @param array $config
     * @return Product|false
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _loadProductWithOptionsBySku($sku, $config = [])
    {
        if (empty($config)) {
            $product = $this->_loadProductBySku($sku);
        } else {
            $product = $this->loadProductBySkuWithConfig($sku, $config);
        }
        if ($product) {
            return $product;
        }

        $product = $this->_loadProductBySku($this->getPrimaryProductSku($sku));

        if (!$product) {
            return false;
        }

        $isProductConfigured = $this->productConfiguration->isProductConfigured($product, $config);
        if ($product && $this->_shouldBeConfigured($product) && $isProductConfigured) {
            return $product;
        }

        if ($product && $product->getId()) {
            $missedRequiredOption = false;
            $this->_successOptions = [];
            $skuParts = $this->getSkuParts($sku, false);
            foreach ($product->getOptionInstance()->getProductOptions($product) as $productOption) {
                if ($productOption->getIsRequire()) {
                    $missedRequiredOption = !$this->_processProductOption($skuParts, $productOption)
                        || $missedRequiredOption;
                } else {
                    $this->_processProductOption($skuParts, $productOption);
                }
            }

            if (!empty($skuParts)) {
                return false;
            }

            if (!$missedRequiredOption && !empty($this->_successOptions)) {
                $product->setConfiguredOptions($this->_successOptions);
                $this->setAffectedItemConfig($sku, ['options' => $this->_successOptions]);
                $this->_successOptions = [];
            }
        }

        return $product;
    }

    /**
     * Check whether specified option could have multiple values
     *
     * @param \Magento\Catalog\Model\Product\Option $option
     * @return bool
     */
    protected function _isOptionMultiple($option)
    {
        switch ($option->getType()) {
            case \Magento\Catalog\Api\Data\ProductCustomOptionInterface::OPTION_TYPE_MULTIPLE:
            case \Magento\Catalog\Api\Data\ProductCustomOptionInterface::OPTION_TYPE_CHECKBOX:
                return true;
            default:
                break;
        }
        return false;
    }

    /**
     * Add product option for configuring
     *
     * @param \Magento\Catalog\Model\Product\Option $option
     * @param \Magento\Catalog\Model\Product\Option\Value $value
     * @return $this
     */
    protected function _addSuccessOption($option, $value)
    {
        if ($this->_isOptionMultiple($option)) {
            if (isset($this->_successOptions[$option->getOptionId()])
                && is_array($this->_successOptions[$option->getOptionId()])
            ) {
                $this->_successOptions[$option->getOptionId()][] = $value->getOptionTypeId();
            } else {
                $this->_successOptions[$option->getOptionId()] = [$value->getOptionTypeId()];
            }
        } else {
            $this->_successOptions[$option->getOptionId()] = $value->getOptionTypeId();
        }

        return $this;
    }

    /**
     * Check whether current context is checkout
     *
     * @codeCoverageIgnore
     * @return bool
     */
    protected function _isCheckout()
    {
        return in_array($this->_context, [self::CONTEXT_FRONTEND, self::CONTEXT_ADMIN_CHECKOUT]);
    }

    /**
     * Check whether current context is frontend
     *
     * @return bool
     */
    protected function _isFrontend()
    {
        return $this->_context == self::CONTEXT_FRONTEND;
    }

    /**
     * Update item with assigning the code to it
     *
     * @param array $item
     * @param string $code
     * @return array
     */
    protected function _updateItem($item, $code)
    {
        $item['code'] = $code;
        return $item;
    }

    /**
     * Check items before adding by SKU
     *
     * @param array $items Example: [['sku' => 'simple1', 'qty' => 2, 'config' => [...]], ...]
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkItems(array $items): array
    {
        $checkedItems = [];
        foreach ($items as $item) {
            if (empty($item['sku'])) {
                continue;
            }
            $sku = $item['sku'];
            $qty = $item['qty'] ?? '';
            $qty = \is_float($qty) && isset($checkedItems[$sku]) ? ($qty + $checkedItems[$sku]['qty']) : $qty;
            $checkedItems[$sku] = $this->_getValidatedItem($sku, $qty);
        }

        $checkedItems = $this->areProductsSalable($checkedItems);
        $products = $this->preloadProducts($checkedItems);
        foreach ($checkedItems as $sku => &$item) {
            $itemProduct = $products[mb_strtolower($sku)] ?? null;
            $item = $this->checkItem(
                $sku,
                $item['qty'],
                [
                    '__item' => $item,
                    'product' => $itemProduct
                ]
            );
        }

        return $checkedItems;
    }

    /**
     * Are product salable
     *
     * @param array $items
     * @return array
     */
    private function areProductsSalable(array $items): array
    {
        if ($this->_isCheckout()) {
            $skuQuantities = \array_map(function ($item) {
                return new \Magento\AdvancedCheckout\Model\Data\ProductQuantity(
                    $item['sku'] = $item['sku'] ? $item['sku'] : '',
                    (float)($item['qty'] ?? 0)
                );
            }, $items);

            $itemsStockStatus = $this->areProductsSalableForRequestedQty->execute(
                $skuQuantities,
                (int)$this->getStore()->getWebsiteId()
            );
            foreach ($itemsStockStatus as $stockStatus) {
                if (!$stockStatus->isSalable()) {
                    $item = &$items[$stockStatus->getSku()];
                    $item['is_configure_disabled'] = true;
                    $item['is_qty_disabled'] = true;
                    $item = $this->_updateItem($item, Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK);
                }
            }
        }
        return $items;
    }

    /**
     * Check item before adding by SKU
     *
     * @param string $sku
     * @param float $qty
     * @param array $config Configuration data of the product (if has been configured)
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function checkItem($sku, $qty, $config = [])
    {
        if (array_key_exists('product', $config)) {
            unset($config['product']);
        }

        if (isset($config['__item'])) {
            $item = $config['__item'];
            unset($config['__item']);
        } else {
            $item = $this->_getValidatedItem($sku, $qty);
        }
        if ($item['code'] == Data::ADD_ITEM_STATUS_FAILED_EMPTY) {
            return $item;
        }
        if (!empty($config)) {
            $this->setAffectedItemConfig($sku, $config);
        }
        /** @var $product Product */
        $product = $this->_loadProductWithOptionsBySku($item['sku'], $config);
        if ($item['code'] === Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK && $product && $product->isSalable() === true) {
            $item['code'] = Data::ADD_ITEM_STATUS_SUCCESS;
        }

        if ($product && $product->hasConfiguredOptions()) {
            $config['options'] = $product->getConfiguredOptions();
        }

        if ($product && $product->getId()) {
            $item['id'] = $product->getId();
            $item['is_qty_disabled'] = $this->productTypeConfig->isProductSet($product->getTypeId());

            if ($this->_isCheckout() && $product->isDisabled()) {
                $item['is_configure_disabled'] = true;
                $failCode = $this->_context == self::CONTEXT_FRONTEND
                    ? Data::ADD_ITEM_STATUS_FAILED_SKU
                    : Data::ADD_ITEM_STATUS_FAILED_DISABLED;
                return $this->_updateItem($item, $failCode);
            }

            if ($this->_isFrontend() && true === $product->getDisableAddToCart()) {
                return $this->_updateItem($item, Data::ADD_ITEM_STATUS_FAILED_PERMISSIONS);
            }

            $productWebsiteValidationResult = $this->_validateProductWebsite($product);
            if ($productWebsiteValidationResult !== true) {
                $item['is_configure_disabled'] = true;
                return $this->_updateItem($item, $productWebsiteValidationResult);
            }

            if ($item['code'] !== Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK && $this->_shouldBeConfigured($product)) {
                if (!$this->productConfiguration->isProductConfigured($product, $config)) {
                    $failCode = !$this->_isFrontend() || $product->isVisibleInSiteVisibility()
                        ? Data::ADD_ITEM_STATUS_FAILED_CONFIGURE
                        : Data::ADD_ITEM_STATUS_FAILED_SKU;
                    return $this->_updateItem($item, $failCode);
                } else {
                    $item['code'] = Data::ADD_ITEM_STATUS_SUCCESS;
                }
            }
            if ($item['code'] != Data::ADD_ITEM_STATUS_SUCCESS) {
                return $this->_updateItem($item, $item['code']);
            }

            if ($this->_isFrontend() && !$item['is_qty_disabled']) {
                $qtyStatus = $this->getQtyStatus($product, $item['qty']);
                if ($qtyStatus === true) {
                    return $this->_updateItem($item, Data::ADD_ITEM_STATUS_SUCCESS);
                } else {
                    $item['code'] = $qtyStatus['status'];
                    unset($qtyStatus['status']);
                    // Add qty_max_allowed and qty_min_allowed, if present
                    $item = array_merge($item, $qtyStatus);
                    return $item;
                }
            }
        } else {
            return $this->_updateItem($item, Data::ADD_ITEM_STATUS_FAILED_SKU);
        }

        return $this->_updateItem($item, Data::ADD_ITEM_STATUS_SUCCESS);
    }

    /**
     * Check product availability for current website
     *
     * @param Product $product
     * @return bool|string
     */
    protected function _validateProductWebsite($product)
    {
        if (in_array($this->getCurrentStore()->getWebsiteId(), $product->getWebsiteIds())) {
            return true;
        }

        return $this->_itemFailedStatus;
    }

    /**
     * Returns validated item
     *
     * @param string|array $sku
     * @param string|int|float $qty
     *
     * @return array
     */
    protected function _getValidatedItem($sku, $qty)
    {
        $code = Data::ADD_ITEM_STATUS_SUCCESS;
        if ($sku === '') {
            $code = Data::ADD_ITEM_STATUS_FAILED_EMPTY;
        } else {
            if (is_float($qty) || is_int($qty) || ValidatorChain::is((string)$qty, FloatUtils::class)) {
                $qty = $this->_localeFormat->getNumber($qty);
                if ($qty <= 0) {
                    $code = Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NON_POSITIVE;
                } elseif ($qty < 0.0001 || $qty > 99999999.9999) {
                    // same as app/design/frontend/enterprise/default/template/checkout/widget/sku.phtml
                    $code = Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_RANGE;
                }
            } else {
                $code = Data::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NUMBER;
            }
        }

        if ($code != Data::ADD_ITEM_STATUS_SUCCESS) {
            $qty = '';
        }

        return ['sku' => $sku, 'qty' => $qty, 'code' => $code];
    }

    /**
     * Check whether specified product is out of stock
     *
     * @param Product $product
     * @return bool
     * @deprecated use \Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQtyInterface::execute instead
     *
     * @see \Magento\AdvancedCheckout\Model\AreProductsSalableForRequestedQtyInterface
     */
    protected function _isProductOutOfStock($product)
    {
        return !$this->isProductInStock->execute((int)$product->getId(), (int)$this->getStore()->getWebsiteId());
    }

    /**
     * Check whether specified product should be configured
     *
     * @param Product $product
     * @return bool
     */
    protected function _shouldBeConfigured($product)
    {
        if ($product->getTypeId() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE
            && !$product->getLinksPurchasedSeparately()
        ) {
            return false;
        }

        if ($product->isComposite() || $product->getRequiredOptions()) {
            return true;
        }

        switch ($product->getTypeId()) {
            case \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD:
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                return true;
            default:
                break;
        }

        return false;
    }

    /**
     * Set config for specific item
     *
     * @param string $sku
     * @param array $config
     * @return $this
     */
    public function setAffectedItemConfig($sku, $config)
    {
        if ($sku !== '' && !empty($config) && is_array($config)) {
            $this->_affectedItemsConfig[$sku] = $config;
        }
        return $this;
    }

    /**
     * Return config of specific item
     *
     * @param string $sku
     * @return array
     */
    public function getAffectedItemConfig($sku)
    {
        return isset($this->_affectedItemsConfig[$sku]) ? $this->_affectedItemsConfig[$sku] : [];
    }

    /**
     * Add products previously successfully processed by prepareAddProductsBySku() to cart
     *
     * @param \Magento\Checkout\Model\Cart\CartInterface|null $cart Custom cart model (different from
     *                                                              checkout/cart)
     * @param bool $saveQuote Whether cart quote should be saved
     * @return $this
     */
    public function saveAffectedProducts(\Magento\Checkout\Model\Cart\CartInterface $cart = null, $saveQuote = true)
    {
        $cart = $cart ? $cart : $this->_cart;
        $affectedItems = $this->getAffectedItems();
        foreach ($affectedItems as &$item) {
            if ($item['code'] == Data::ADD_ITEM_STATUS_SUCCESS) {
                $this->_safeAddProduct($item, $cart);
            }
        }
        $this->setAffectedItems($affectedItems);
        $this->removeSuccessItems();
        if ($saveQuote) {
            $cart->saveQuote();
        }
        return $this;
    }

    /**
     * Create temporary quote, which will incapsulate non-checked data.
     *
     * Under unchecked data, means, some data that can not pass validation or etc
     *
     * @param Quote $quote
     * @return Quote
     */
    private function cloneQuote(Quote $quote)
    {
        // copy data to temporary quote
        /** @var $temporaryQuote \Magento\Quote\Model\Quote */
        $temporaryQuote = $this->quoteFactory->create();
        $temporaryQuote->setData($quote->getData());
        $temporaryQuote->setId(null);//as it is clone, we need to flush ids
        $temporaryQuote->setStore($quote->getStore())->setIsSuperMode($quote->getIsSuperMode());
        /** @var Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            $temporaryItem = clone $quoteItem;
            $temporaryItem->setQuote($temporaryQuote);
            $temporaryQuote->addItem($temporaryItem);
            $quoteItem->setClonnedItem($temporaryItem);

            //Check for parent item
            $parentItem = null;
            if ($quoteItem->getParentItem()) {
                $parentItem = $quoteItem->getParentItem();
                $temporaryItem->setParentProductId(null);
            } elseif ($quoteItem->getParentProductId()) {
                $parentItem = $quote->getItemById($quoteItem->getParentProductId());
            }
            if ($parentItem && $parentItem->getClonnedItem()) {
                $temporaryItem->setParentItem($parentItem->getClonnedItem());
            }
        }

        return $temporaryQuote;
    }

    /**
     * Safely add product to cart, revert cart in error case
     *
     * @param array $item
     * @param \Magento\Checkout\Model\Cart\CartInterface $cart
     * @param bool $suppressSuperMode
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _safeAddProduct(
        &$item,
        \Magento\Checkout\Model\Cart\CartInterface $cart,
        $suppressSuperMode = false
    ) {
        $quote = $cart->getQuote();

        $temporaryQuote = $this->cloneQuote($quote);
        $cart->setQuote($temporaryQuote);
        $success = true;
        $skipCheckQty = !$suppressSuperMode
            && $this->_isCheckout()
            && !$this->_isFrontend()
            && empty($item['item']['is_qty_disabled'])
            && !$cart->getQuote()->getIsSuperMode();
        if ($skipCheckQty) {
            $cart->getQuote()->setIsSuperMode(true);
        }

        try {
            $config = $this->getAffectedItemConfig($item['item']['sku']);
            if (!empty($config)) {
                $config['qty'] = $item['item']['qty'];
            } else {
                // If second parameter of addProduct() is not an array than it is considered to be qty
                $config = $item['item']['qty'];
            }
            $storeId = $this->getCurrentStore()->getId();
            $product = $this->getProductFromLocalCache($item['item']['sku'], $storeId) ?? $item['item']['id'];
            $cart->addProduct($product, $config);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if (!$suppressSuperMode) {
                $success = false;
                $item['code'] = Data::ADD_ITEM_STATUS_FAILED_UNKNOWN;
                if ($this->_isFrontend()) {
                    $item['item']['error'] = $e->getMessage();
                } else {
                    $item['error'] = $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            $success = false;
            $item['code'] = Data::ADD_ITEM_STATUS_FAILED_UNKNOWN;
            $error = __('We can\'t add the item to your cart.');
            if ($this->_isFrontend()) {
                $item['item']['error'] = $error;
            } else {
                $item['error'] = $error;
            }
        }
        if ($skipCheckQty) {
            $cart->getQuote()->setIsSuperMode(false);
            if ($success) {
                $cart->setQuote($quote);
                // we need add products with checking their stock qty
                return $this->_safeAddProduct($item, $cart, true);
            }
        }

        if ($success) {
            // copy temporary data to real quote
            $quote->removeAllItems();
            foreach ($temporaryQuote->getAllItems() as $quoteItem) {
                $quoteItem->setQuote($quote);
                $quote->addItem($quoteItem);
            }
        }

        $cart->setQuote($quote);
        return $this;
    }

    /**
     * Returns affected items
     * Return format:
     * sku(string) => [
     *  'item' => [
     *      'sku'             => string,
     *      'qty'             => int,
     *      'id'              => int (optional, if product does exist),
     *      'qty_max_allowed' => int (optional, if 'code'==ADD_ITEM_STATUS_FAILED_QTY_ALLOWED)
     *  ],
     *  'code' => string (see \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_*)
     * ]
     *
     * @param null|int $storeId
     * @return array
     *
     * @see prepareAddProductsBySku()
     */
    public function getAffectedItems($storeId = null)
    {
        $storeId = $storeId === null ? $this->getCurrentStore()->getId() : (int)$storeId;
        $affectedItems = $this->_getHelper()->getSession()->getAffectedItems();

        return isset($affectedItems[$storeId]) && is_array($affectedItems[$storeId]) ? $affectedItems[$storeId] : [];
    }

    /**
     * Returns only items with 'success' status
     *
     * @return array
     */
    public function getSuccessfulAffectedItems()
    {
        $items = [];
        foreach ($this->getAffectedItems() as $item) {
            if ($item['code'] == Data::ADD_ITEM_STATUS_SUCCESS) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Set affected items
     *
     * @param array $items
     * @param null|int $storeId
     * @return $this
     */
    public function setAffectedItems($items, $storeId = null)
    {
        $storeId = $storeId === null ? $this->getCurrentStore()->getId() : (int)$storeId;
        $affectedItems = $this->_getHelper()->getSession()->getAffectedItems();
        if (!is_array($affectedItems)) {
            $affectedItems = [];
        }

        $affectedItems[$storeId] = $items;
        $this->_getHelper()->getSession()->setAffectedItems($affectedItems);
        return $this;
    }

    /**
     * Retrieve info message
     *
     * @return MessageInterface[]
     */
    public function getMessages()
    {
        $affectedItems = $this->getAffectedItems();
        $currentlyAffectedItemsCount = count($this->_currentlyAffectedItems);
        $currentlyFailedItemsCount = 0;

        foreach ($this->_currentlyAffectedItems as $sku) {
            if (isset($affectedItems[$sku]) && $affectedItems[$sku]['code'] != Data::ADD_ITEM_STATUS_SUCCESS) {
                $currentlyFailedItemsCount++;
            }
        }

        $addedItemsCount = $currentlyAffectedItemsCount - $currentlyFailedItemsCount;

        $failedItemsCount = count($this->getFailedItems());
        $messages = [];
        if ($addedItemsCount) {
            if ($addedItemsCount == 1) {
                $message = __('You added %1 product to your shopping cart.', $addedItemsCount);
            } else {
                $message = __('You added %1 products to your shopping cart.', $addedItemsCount);
            }
            $messages[] = $this->messageFactory->create(MessageInterface::TYPE_SUCCESS, $message);
        }
        if ($failedItemsCount) {
            if ($failedItemsCount == 1) {
                $warning = __('%1 product requires your attention.', $failedItemsCount);
            } else {
                $warning = __('%1 products require your attention.', $failedItemsCount);
            }
            $messages[] = $this->messageFactory->create(MessageInterface::TYPE_ERROR, $warning);
        }
        return $messages;
    }

    /**
     * Retrieve list of failed items. For return format see getAffectedItems().
     *
     * @return array
     */
    public function getFailedItems()
    {
        $failedItems = [];
        foreach ($this->getAffectedItems() as $item) {
            if ($item['code'] != Data::ADD_ITEM_STATUS_SUCCESS) {
                $failedItems[] = $item;
            }
        }
        return $failedItems;
    }

    /**
     * Add processed item to stack.
     * Return format:
     * [
     *  'item' => [
     *      'sku'             => string,
     *      'qty'             => int,
     *      'id'              => int (optional, if product does exist),
     *      'qty_max_allowed' => int (optional, if 'code'==ADD_ITEM_STATUS_FAILED_QTY_ALLOWED)
     *  ],
     *  'code' => string (see \Magento\AdvancedCheckout\Helper\Data::ADD_ITEM_STATUS_*),
     *  'orig_qty' => string|int|float
     * ]
     *
     * @param array $item
     * @param string $code
     * @return array|$this
     */
    protected function _addAffectedItem($item, $code)
    {
        if (!isset($item['sku']) || $code == Data::ADD_ITEM_STATUS_FAILED_EMPTY) {
            return $this;
        }
        $sku = $item['sku'];
        $affectedItems = $this->getAffectedItems();
        $affectedItems[$sku] = ['item' => $item, 'code' => $code, 'orig_qty' => $item['qty']];
        $this->_currentlyAffectedItems[] = $sku;
        $this->setAffectedItems($affectedItems);
        return $affectedItems[$sku];
    }

    /**
     * Update qty of specified item
     *
     * @param string $sku
     * @param int $qty
     * @return $this
     */
    public function updateItemQty($sku, $qty)
    {
        $affectedItems = $this->getAffectedItems();
        if (isset($affectedItems[$sku])) {
            $affectedItems[$sku]['item']['qty'] = $qty;
        }
        $this->setAffectedItems($affectedItems);
        return $this;
    }

    /**
     * Remove item from storage by specified key(sku)
     *
     * @param string $sku
     * @return bool
     */
    public function removeAffectedItem($sku)
    {
        $affectedItems = $this->getAffectedItems();
        if (isset($affectedItems[$sku])) {
            unset($affectedItems[$sku]);
            $this->setAffectedItems($affectedItems);
            return true;
        }
        return false;
    }

    /**
     * Remove all affected items from storage
     *
     * @codeCoverageIgnore
     * @return $this
     */
    public function removeAllAffectedItems()
    {
        $this->setAffectedItems([]);
        return $this;
    }

    /**
     * Remove all affected items with code=success
     *
     * @return $this
     */
    public function removeSuccessItems()
    {
        $affectedItems = $this->getAffectedItems();
        foreach ($affectedItems as $key => $item) {
            if ($item['code'] == Data::ADD_ITEM_STATUS_SUCCESS) {
                unset($affectedItems[$key]);
            }
        }
        $this->setAffectedItems($affectedItems);
        return $this;
    }

    /**
     * Retrieve helper instance
     *
     * @codeCoverageIgnore
     * @return \Magento\AdvancedCheckout\Helper\Data
     */
    protected function _getHelper()
    {
        return $this->_checkoutData;
    }

    /**
     * Sets session where data is going to be stored
     *
     * @codeCoverageIgnore
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @return $this
     */
    public function setSession(\Magento\Framework\Session\SessionManagerInterface $session)
    {
        $this->_getHelper()->setSession($session);
        return $this;
    }

    /**
     * Returns current session used to store data about affected items
     *
     * @codeCoverageIgnore
     * @return \Magento\Framework\Session\SessionManagerInterface
     */
    public function getSession()
    {
        return $this->_getHelper()->getSession();
    }

    /**
     * Retrieve instance of current store
     *
     * @return \Magento\Store\Model\Store
     */
    public function getCurrentStore()
    {
        if (null === $this->_currentStore) {
            return $this->_storeManager->getStore();
        }
        return $this->_currentStore;
    }

    /**
     * Set current store
     *
     * @param mixed $store
     * @return $this
     */
    public function setCurrentStore($store)
    {
        if (null !== $store) {
            $this->_currentStore = $this->_storeManager->getStore($store);
        }
        return $this;
    }

    /**
     * Load and add to cache required products by skus.
     *
     * @param array $items
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function preloadProducts(array $items)
    {
        $skuForFind = array_diff(array_keys($items), array_keys($this->products));
        $products = [];
        if ($skuForFind) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addFieldToFilter('sku', ['in' => $skuForFind]);
            $collection->setFlag('has_stock_status_filter', false);
            $itemsLowerCase = array_combine(array_map('mb_strtolower', array_keys($items)), $items);
            foreach ($collection as $product) {
                $sku = $product->getSku();
                $isSalable = true;
                if (!isset($itemsLowerCase[mb_strtolower($sku)]) ||
                    $itemsLowerCase[mb_strtolower($sku)]['code'] === Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK
                ) {
                    $isSalable = false;
                }
                $product->setIsSalable($isSalable);
                $products[mb_strtolower($sku)] = $product;
                $this->addProductToLocalCache($product, $product->getStoreId());
            }
        }

        return $products;
    }

    /**
     * Gets product from the local cache.
     *
     * @param string $sku
     * @param int $storeId
     * @return ProductInterface|null
     */
    private function getProductFromLocalCache(string $sku, int $storeId)
    {
        if (!isset($this->products[$sku])) {
            return null;
        }

        return $this->products[$sku][$storeId] ?? null;
    }

    /**
     * Add product to the local cache.
     *
     * @param ProductInterface $product
     * @param int $storeId
     * @return void
     */
    private function addProductToLocalCache(ProductInterface $product, int $storeId)
    {
        $this->products[$product->getSku()][$storeId] = $product;
    }

    /**
     * Get primary product SKU
     *
     * @param string $sku
     * @return string
     */
    private function getPrimaryProductSku(string $sku)
    {
        $skuParts = $this->getSkuParts($sku);
        $primarySku = array_shift($skuParts);

        if (empty($primarySku) || $primarySku === $sku) {
            $primarySku = $sku;
        }

        return $primarySku;
    }

    /**
     * Get SKU parts by separating string with '-' symbol
     *
     * @param string $sku
     * @param bool $includePrimary
     * @return array
     */
    private function getSkuParts(string $sku, $includePrimary = true)
    {
        $skuParts = explode('-', $sku);
        if (!$includePrimary) {
            array_shift($skuParts);
        }
        return $skuParts;
    }
}
