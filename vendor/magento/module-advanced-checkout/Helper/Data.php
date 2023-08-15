<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Helper;

use Magento\Quote\Model\Quote\Item;

/**
 * Enterprise Checkout Helper
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Items for requiring attention grid (doesn't include sku-failed items)
     *
     * @var null|Item[]
     */
    protected $_items;

    /**
     * Items for requiring attention grid (including sku-failed items)
     *
     * @var null|Item[]
     */
    protected $_itemsAll;

    /**
     * Config path to Enable Order By SKU tab in the Customer account dashboard and Allowed groups
     */
    const XML_PATH_SKU_ENABLED = 'sales/product_sku/my_account_enable';

    const XML_PATH_SKU_ALLOWED_GROUPS = 'sales/product_sku/allowed_groups';

    /**
     * Status of item, that was added by SKU
     */
    const ADD_ITEM_STATUS_SUCCESS = 'success';

    const ADD_ITEM_STATUS_FAILED_SKU = 'failed_sku';

    const ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK = 'failed_out_of_stock';

    const ADD_ITEM_STATUS_FAILED_QTY_ALLOWED = 'failed_qty_allowed';

    const ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART = 'failed_qty_allowed_in_cart';

    const ADD_ITEM_STATUS_FAILED_QTY_INVALID_NUMBER = 'failed_qty_invalid_number';

    const ADD_ITEM_STATUS_FAILED_QTY_INVALID_NON_POSITIVE = 'failed_qty_invalid_non_positive';

    const ADD_ITEM_STATUS_FAILED_QTY_INVALID_RANGE = 'failed_qty_invalid_range';

    const ADD_ITEM_STATUS_FAILED_QTY_INCREMENTS = 'failed_qty_increment';

    const ADD_ITEM_STATUS_FAILED_CONFIGURE = 'failed_configure';

    const ADD_ITEM_STATUS_FAILED_PERMISSIONS = 'failed_permissions';

    const ADD_ITEM_STATUS_FAILED_WEBSITE = 'failed_website';

    const ADD_ITEM_STATUS_FAILED_UNKNOWN = 'failed_unknown';

    const ADD_ITEM_STATUS_FAILED_EMPTY = 'failed_empty';

    const ADD_ITEM_STATUS_FAILED_DISABLED = 'failed_disabled';

    /**
     * Request parameter name, which indicates, whether file was uploaded
     */
    const REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG = 'sku_file_uploaded';

    /**
     * Customer Groups that allow Order by SKU
     *
     * @var int[]|null
     */
    protected $_allowedGroups;

    /**
     * Contains session object to which data is saved
     *
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_session;

    /**
     * List of item statuses, that should be rendered by 'failed' template
     *
     * @var string[]
     */
    protected $_failedTemplateStatusCodes = [
        self::ADD_ITEM_STATUS_FAILED_SKU,
        self::ADD_ITEM_STATUS_FAILED_PERMISSIONS,
    ];

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogData = null;

    /**
     * Checkout cart
     *
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $_checkoutCart = null;

    /**
     * @var \Magento\AdvancedCheckout\Model\Cart
     */
    protected $_cart;

    /**
     * @var \Magento\AdvancedCheckout\Model\ResourceModel\Product\Collection
     */
    protected $_products;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $_catalogConfig;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Sales quote item factory
     *
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $_quoteItemFactory = null;

    /**
     * Catalog product factory
     *
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory = null;

    /**
     * Catalog inventory stock item service
     *
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry = null;

    /**
     * Catalog inventory stock status service
     *
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockHelper;

    /**
     * Advanced checkout import factory
     *
     * @var \Magento\AdvancedCheckout\Model\ImportFactory
     */
    protected $_importFactory = null;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Msrp\Helper\Data
     */
    protected $msrpData;

    /**
     * @var \Magento\Customer\Api\GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\AdvancedCheckout\Model\Cart $cart
     * @param \Magento\AdvancedCheckout\Model\ResourceModel\Product\Collection $products
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Helper\Cart $checkoutCart
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\AdvancedCheckout\Model\ImportFactory $importFactory
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Msrp\Helper\Data $msrpData
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagement
     *
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\AdvancedCheckout\Model\Cart $cart,
        \Magento\AdvancedCheckout\Model\ResourceModel\Product\Collection $products,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Cart $checkoutCart,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\AdvancedCheckout\Model\ImportFactory $importFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Msrp\Helper\Data $msrpData,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_cart = $cart;
        $this->_products = $products;
        $this->_catalogConfig = $catalogConfig;
        $this->_session = $session;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_checkoutCart = $checkoutCart;
        $this->_catalogData = $catalogData;
        parent::__construct($context);
        $this->_importFactory = $importFactory;
        $this->stockRegistry = $stockRegistry;
        $this->stockHelper = $stockHelper;
        $this->_productFactory = $productFactory;
        $this->_quoteItemFactory = $quoteItemFactory;
        $this->messageManager = $messageManager;
        $this->msrpData = $msrpData;
        $this->groupManagement = $groupManagement;
    }

    /**
     * Return session for affected items
     *
     * @codeCoverageIgnore
     * @return \Magento\Framework\Session\SessionManagerInterface
     */
    public function getSession()
    {
        return $this->_session;
    }

    /**
     * Sets session instance to use for saving data
     *
     * @codeCoverageIgnore
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @return void
     */
    public function setSession(\Magento\Framework\Session\SessionManagerInterface $session)
    {
        $this->_session = $session;
    }

    /**
     * Retrieve error message for the item
     *
     * @param \Magento\Framework\DataObject $item
     * @return string
     */
    public function getMessageByItem(\Magento\Framework\DataObject $item)
    {
        $message = $this->getMessage($item->getCode());
        return $message ? $message : $item->getError();
    }

    /**
     * Retrieve message by specified error code
     *
     * @param string $code
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getMessage($code)
    {
        switch ($code) {
            case self::ADD_ITEM_STATUS_FAILED_SKU:
                $message = __('The SKU was not found in the catalog.');
                break;
            case self::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK:
                $message = __('Availability: Out of stock.');
                break;
            case self::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED:
                $message = __('We don\'t have as many of these as you want.');
                break;
            case self::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART:
                $message = __('You can\'t add this many to your cart.');
                break;
            case self::ADD_ITEM_STATUS_FAILED_CONFIGURE:
                $message = __('You need to choose options for your item.');
                break;
            case self::ADD_ITEM_STATUS_FAILED_PERMISSIONS:
                $message = __('We can\'t add the item to your cart.');
                break;
            case self::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NUMBER:
            case self::ADD_ITEM_STATUS_FAILED_QTY_INVALID_NON_POSITIVE:
            case self::ADD_ITEM_STATUS_FAILED_QTY_INVALID_RANGE:
                $message = __('Please enter an actual number in the "Qty" field.');
                break;
            case self::ADD_ITEM_STATUS_FAILED_WEBSITE:
                $message = __('This product is assigned to another website.');
                break;
            case self::ADD_ITEM_STATUS_FAILED_DISABLED:
                $message = __('You can add only enabled products.');
                break;
            default:
                $message = '';
        }
        return $message;
    }

    /**
     * Check whether module enabled
     *
     * @return bool
     */
    public function isSkuEnabled()
    {
        $storeData = $this->scopeConfig->getValue(
            self::XML_PATH_SKU_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return \Magento\AdvancedCheckout\Model\Cart\Sku\Source\Settings::NO_VALUE != $storeData;
    }

    /**
     * Check whether Order by SKU functionality applicable to the current customer
     *
     * @return bool
     */
    public function isSkuApplied()
    {
        $result = false;
        $data = $this->scopeConfig->getValue(
            self::XML_PATH_SKU_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        switch ($data) {
            case \Magento\AdvancedCheckout\Model\Cart\Sku\Source\Settings::YES_VALUE:
                $result = true;
                break;
            case \Magento\AdvancedCheckout\Model\Cart\Sku\Source\Settings::YES_SPECIFIED_GROUPS_VALUE:
                if ($this->_customerSession) {
                    $groupId = $this->_customerSession->getCustomerGroupId();
                    $result = $groupId === $this->groupManagement->getNotLoggedInGroup()->getId()
                        || in_array($groupId, $this->getSkuCustomerGroups());
                }
                break;
        }
        return $result;
    }

    /**
     * Retrieve Customer Groups that allow Order by SKU from config
     *
     * @return int[]
     */
    public function getSkuCustomerGroups()
    {
        if ($this->_allowedGroups === null) {
            $this->_allowedGroups = explode(
                ',',
                trim(
                    $this->scopeConfig->getValue(
                        self::XML_PATH_SKU_ALLOWED_GROUPS,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )
            );
        }
        return $this->_allowedGroups;
    }

    /**
     * Get add by SKU failed items
     *
     * @param bool $all whether sku-failed items should be retrieved
     * @return Item[]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getFailedItems($all = true)
    {
        if ($all && $this->_itemsAll === null || !$all && $this->_items === null) {
            $failedItems = $this->_cart->getFailedItems();
            $collection = $this->_products->addMinimalPrice()->addFinalPrice()->addTaxPercents()->addAttributeToSelect(
                $this->_catalogConfig->getProductAttributes()
            )->addUrlRewrite();
            $itemsToLoad = [];

            $quoteItemsCollection = $this->_items === null ? [] : $this->_items;
            $quote = $this->_checkoutSession->getQuote();
            foreach ($failedItems as $item) {
                if ($this->_items === null && !in_array($item['code'], $this->_failedTemplateStatusCodes)) {
                    $id = $item['item']['id'];
                    if (!isset($itemsToLoad[$id])) {
                        $itemsToLoad[$id] = [];
                    }
                    $itemToLoad = $item['item'];
                    $itemToLoad['code'] = $item['code'];
                    $itemToLoad['error'] = isset($item['item']['error']) ? $item['item']['error'] : '';
                    // Avoid collisions of product ID with quote item ID
                    unset($itemToLoad['id']);
                    $itemsToLoad[$id][] = $itemToLoad;
                } elseif ($all && in_array($item['code'], $this->_failedTemplateStatusCodes)) {
                    $item['item']['code'] = $item['code'];
                    $item['item']['product_type'] = 'undefined';
                    // Create empty quote item. Otherwise it won't be correctly treated inside failed.phtml
                    $collectionItem = $this->_quoteItemFactory->create()->setProduct(
                        $this->_productFactory->create()
                    )->addData(
                        $item['item']
                    );
                    $quoteItemsCollection[] = $collectionItem;
                }
            }
            $ids = array_keys($itemsToLoad);
            if ($ids) {
                $collection->addIdFilter($ids);
                // Flag prevents adding of "is in stock filter" to the collection
                // @see \Magento\CatalogInventory\Helper\Stock::addIsInStockFilterToCollection
                $collection->setFlag('has_stock_status_filter', true);

                $emptyQuoteItem = $this->_quoteItemFactory->create();

                /** @var $itemProduct \Magento\Catalog\Model\Product */
                foreach ($collection->getItems() as $product) {
                    $itemsCount = count($itemsToLoad[$product->getId()]);
                    foreach ($itemsToLoad[$product->getId()] as $index => $itemToLoad) {
                        $itemProduct = $index == $itemsCount - 1 ? $product : clone $product;
                        $itemProduct->addData($itemToLoad);
                        if (!$itemProduct->getOptionsByCode()) {
                            $itemProduct->setOptionsByCode([]);
                        }
                        // Create a new quote item and import data to it
                        $quoteItem = clone $emptyQuoteItem;
                        $quoteItem->addData($itemProduct->getData())
                            ->setQuote($quote)
                            ->setProduct($itemProduct)
                            ->setOptions($itemProduct->getOptions())
                            ->setRedirectUrl($itemProduct->getUrlModel()->getUrl($itemProduct));

                        $itemProduct->setCustomOptions($itemProduct->getOptionsByCode());
                        if ($this->msrpData->canApplyMsrp($itemProduct)
                            && $this->msrpData->isMinimalPriceLessMsrp($itemProduct)
                        ) {
                            $quoteItem->setCanApplyMsrp(true);
                            $itemProduct->setRealPriceHtml(
                                $this->priceCurrency->convertAndFormat(
                                    $this->_catalogData->getTaxPrice(
                                        $itemProduct,
                                        $itemProduct->getFinalPrice(),
                                        true
                                    )
                                )
                            );
                            $itemProduct->setAddToCartUrl($this->_checkoutCart->getAddUrl($itemProduct));
                        } else {
                            $quoteItem->setCanApplyMsrp(false);
                        }

                        $this->stockHelper->assignStatusToProduct($itemProduct);
                        $stockItemDo = $this->stockRegistry->getStockItem(
                            $itemProduct->getId(),
                            $quote->getStore()->getWebsiteId()
                        );
                        $quoteItem->setStockItem($stockItemDo);
                        $quoteItemsCollection[] = $quoteItem;
                    }
                }
            }

            if ($all) {
                $this->_itemsAll = $quoteItemsCollection;
            } else {
                $this->_items = $quoteItemsCollection;
            }
        }
        return $all ? $this->_itemsAll : $this->_items;
    }

    /**
     * Get text of general error while file uploading
     *
     * @codeCoverageIgnore
     * @return \Magento\Framework\Phrase
     */
    public function getFileGeneralErrorText()
    {
        return __('You cannot upload this file.');
    }

    /**
     * Process SKU file uploading and get uploaded data
     *
     * @return array|null
     */
    public function processSkuFileUploading()
    {
        $rows = null;
        $importModel = $this->_importFactory->create();

        try {
            /** @var \Magento\AdvancedCheckout\Model\Import $importModel */
            $importModel->uploadFile();
            $rows = $importModel->getRows();
            if (empty($rows)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The file is empty.'));
            }

            return $rows;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, $this->getFileGeneralErrorText());
        }

        return $rows;
    }

    /**
     * Check whether SKU file was uploaded
     *
     * @codeCoverageIgnore
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    public function isSkuFileUploaded(\Magento\Framework\App\RequestInterface $request)
    {
        return (bool)$request->getPost(self::REQUEST_PARAMETER_SKU_FILE_IMPORTED_FLAG);
    }

    /**
     * Get url of account SKU tab
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function getAccountSkuUrl()
    {
        return $this->_urlBuilder->getUrl('magento_advancedcheckout/sku');
    }

    /**
     * Get text of message in case of empty SKU data error
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSkuEmptyDataMessageText()
    {
        return $this->isSkuApplied()
            ? __(
                'You have not entered a product SKU. Please <a href="%1">click here</a> to add product(s) by SKU.',
                $this->getAccountSkuUrl()
            )
            : __('You have not entered a product SKU.');
    }
}
