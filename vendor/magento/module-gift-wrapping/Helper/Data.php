<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Helper;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\GiftWrapping\Model\System\Config\Source\Display\Type as DisplayType;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Boolean;

/**
 * Gift wrapping default helper
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Gift wrapping allow section in configuration
     */
    const XML_PATH_ALLOWED_FOR_ITEMS = 'sales/gift_options/wrapping_allow_items';

    const XML_PATH_ALLOWED_FOR_ORDER = 'sales/gift_options/wrapping_allow_order';

    /**
     * Gift wrapping tax class
     */
    const XML_PATH_TAX_CLASS = 'tax/classes/wrapping_tax_class';

    /**
     * Shopping cart display settings
     */
    const XML_PATH_PRICE_DISPLAY_CART_WRAPPING = 'tax/cart_display/gift_wrapping';

    const XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD = 'tax/cart_display/printed_card';

    /**
     * Sales display settings
     */
    const XML_PATH_PRICE_DISPLAY_SALES_WRAPPING = 'tax/sales_display/gift_wrapping';

    const XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD = 'tax/sales_display/printed_card';

    /**
     * Gift receipt and printed card settings
     */
    const XML_PATH_ALLOW_GIFT_RECEIPT = 'sales/gift_options/allow_gift_receipt';

    const XML_PATH_ALLOW_PRINTED_CARD = 'sales/gift_options/allow_printed_card';

    const XML_PATH_PRINTED_CARD_PRICE = 'sales/gift_options/printed_card_price';

    /**
     * Constant for gift wrapping taxable item code
     */
    const TAXABLE_ITEM_CODE = 'giftwrapping_code';

    /**
     * Constant for type of taxable item
     */
    const TAXABLE_ITEM_TYPE = 'giftwrapping_type';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory
     */
    protected $quoteDetailsFactory;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory
     */
    protected $quoteDetailsItemFactory;

    /**
     * @var \Magento\Tax\Api\TaxCalculationInterface
     */
    protected $taxCalculationService;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsFactory
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory
     * @param TaxCalculationInterface $taxCalculationService
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory,
        TaxCalculationInterface $taxCalculationService,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->_storeManager = $storeManager;
        $this->quoteDetailsFactory = $quoteDetailsFactory;
        $this->quoteDetailsItemFactory = $quoteDetailsItemFactory;
        $this->taxCalculationService = $taxCalculationService;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context);
    }

    /**
     * Check availablity of gift wrapping for product
     *
     * @param int $productConfig
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function isGiftWrappingAvailableForProduct($productConfig, $store = null)
    {
        if ($productConfig === null || '' === $productConfig || $productConfig == Boolean::VALUE_USE_CONFIG) {
            return $this->isGiftWrappingAvailableForItems($store);
        } else {
            return $productConfig;
        }
    }

    /**
     * Check availablity of gift wrapping on items level
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return string|null
     */
    public function isGiftWrappingAvailableForItems($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_FOR_ITEMS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check availablity of gift wrapping on order level
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return string|null
     */
    public function isGiftWrappingAvailableForOrder($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_FOR_ORDER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check ability to display both prices for printed card
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return string|null
     */
    public function getWrappingTaxClass($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TAX_CLASS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check printed card allow
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return string|null
     */
    public function allowPrintedCard($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ALLOW_PRINTED_CARD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check allow gift receipt
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return string|null
     */
    public function allowGiftReceipt($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ALLOW_GIFT_RECEIPT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Return printed card base price
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return string|null
     */
    public function getPrintedCardPrice($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PRINTED_CARD_PRICE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check ability to display prices including tax for gift wrapping in shopping cart
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displayCartWrappingIncludeTaxPrice($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_BOTH ||
            $configValue == DisplayType::DISPLAY_TYPE_INCLUDING_TAX;
    }

    /**
     * Check ability to display prices excluding tax for gift wrapping in shopping cart
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displayCartWrappingExcludeTaxPrice($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    /**
     * Check ability to display both prices for gift wrapping in shopping cart
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displayCartWrappingBothPrices($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_BOTH;
    }

    /**
     * Check ability to display prices including tax for printed card in shopping cart
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displayCartCardIncludeTaxPrice($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_BOTH ||
            $configValue == DisplayType::DISPLAY_TYPE_INCLUDING_TAX;
    }

    /**
     * Check ability to display both prices for printed card in shopping cart
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displayCartCardBothPrices($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_BOTH;
    }

    /**
     * Check ability to display prices including tax for gift wrapping in backend sales
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displaySalesWrappingIncludeTaxPrice($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_BOTH ||
            $configValue == DisplayType::DISPLAY_TYPE_INCLUDING_TAX;
    }

    /**
     * Check ability to display prices excluding tax for gift wrapping in backend sales
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displaySalesWrappingExcludeTaxPrice($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    /**
     * Check ability to display both prices for gift wrapping in backend sales
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displaySalesWrappingBothPrices($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_BOTH;
    }

    /**
     * Check ability to display prices including tax for printed card in backend sales
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displaySalesCardIncludeTaxPrice($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_BOTH ||
            $configValue == DisplayType::DISPLAY_TYPE_INCLUDING_TAX;
    }

    /**
     * Check ability to display both prices for printed card in backend sales
     *
     * @param \Magento\Store\Model\Store|int|null $store
     * @return bool
     */
    public function displaySalesCardBothPrices($store = null)
    {
        $configValue = $this->scopeConfig->getValue(
            self::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $configValue == DisplayType::DISPLAY_TYPE_BOTH;
    }

    /**
     * Return totals of data object
     *
     * @param  \Magento\Framework\DataObject $dataObject
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getTotals($dataObject)
    {
        $totals = [];

        $displayWrappingBothPrices = false;
        $displayWrappingIncludeTaxPrice = false;
        $displayCardBothPrices = false;
        $displayCardIncludeTaxPrice = false;

        if ($dataObject instanceof \Magento\Sales\Model\Order ||
            $dataObject instanceof \Magento\Sales\Model\Order\Invoice ||
            $dataObject instanceof \Magento\Sales\Model\Order\Creditmemo
        ) {
            $displayWrappingBothPrices = $this->displaySalesWrappingBothPrices();
            $displayWrappingIncludeTaxPrice = $this->displaySalesWrappingIncludeTaxPrice();
            $displayCardBothPrices = $this->displaySalesCardBothPrices();
            $displayCardIncludeTaxPrice = $this->displaySalesCardIncludeTaxPrice();
        } elseif ($dataObject instanceof \Magento\Quote\Model\Quote\Address\Total) {
            $displayWrappingBothPrices = $this->displayCartWrappingBothPrices();
            $displayWrappingIncludeTaxPrice = $this->displayCartWrappingIncludeTaxPrice();
            $displayCardBothPrices = $this->displayCartCardBothPrices();
            $displayCardIncludeTaxPrice = $this->displayCartCardIncludeTaxPrice();
        }

        /**
         * Gift wrapping for order totals
         */
        if ($displayWrappingBothPrices || $displayWrappingIncludeTaxPrice) {
            if ($displayWrappingBothPrices) {
                $this->_addTotalToTotals(
                    $totals,
                    'gw_order_excl',
                    $dataObject->getGwPrice(),
                    $dataObject->getGwBasePrice(),
                    'Gift Wrapping for Order (Excl. Tax)'
                );
            }
            $this->_addTotalToTotals(
                $totals,
                'gw_order_incl',
                $dataObject->getGwPrice() + $dataObject->getGwTaxAmount(),
                $dataObject->getGwBasePrice() + $dataObject->getGwBaseTaxAmount(),
                'Gift Wrapping for Order (Incl. Tax)'
            );
        } else {
            $this->_addTotalToTotals(
                $totals,
                'gw_order',
                $dataObject->getGwPrice(),
                $dataObject->getGwBasePrice(),
                'Gift Wrapping for Order'
            );
        }

        /**
         * Gift wrapping for items totals
         */
        if ($displayWrappingBothPrices || $displayWrappingIncludeTaxPrice) {
            $this->_addTotalToTotals(
                $totals,
                'gw_items_incl',
                $dataObject->getGwItemsPrice() + $dataObject->getGwItemsTaxAmount(),
                $dataObject->getGwItemsBasePrice() + $dataObject->getGwItemsBaseTaxAmount(),
                'Gift Wrapping for Items (Incl. Tax)'
            );
            if ($displayWrappingBothPrices) {
                $this->_addTotalToTotals(
                    $totals,
                    'gw_items_excl',
                    $dataObject->getGwItemsPrice(),
                    $dataObject->getGwItemsBasePrice(),
                    'Gift Wrapping for Items (Excl. Tax)'
                );
            }
        } else {
            $this->_addTotalToTotals(
                $totals,
                'gw_items',
                $dataObject->getGwItemsPrice(),
                $dataObject->getGwItemsBasePrice(),
                'Gift Wrapping for Items'
            );
        }

        /**
         * Printed card totals
         */
        if ($displayCardBothPrices || $displayCardIncludeTaxPrice) {
            $this->_addTotalToTotals(
                $totals,
                'gw_printed_card_incl',
                $dataObject->getGwCardPrice() + $dataObject->getGwCardTaxAmount(),
                $dataObject->getGwCardBasePrice() + $dataObject->getGwCardBaseTaxAmount(),
                'Printed Card (Incl. Tax)'
            );
            if ($displayCardBothPrices) {
                $this->_addTotalToTotals(
                    $totals,
                    'gw_printed_card_excl',
                    $dataObject->getGwCardPrice(),
                    $dataObject->getGwCardBasePrice(),
                    'Printed Card (Excl. Tax)'
                );
            }
        } else {
            $this->_addTotalToTotals(
                $totals,
                'gw_printed_card',
                $dataObject->getGwCardPrice(),
                $dataObject->getGwCardBasePrice(),
                'Printed Card'
            );
        }

        return $totals;
    }

    /**
     * Add total into array totals
     *
     * @param  array &$totals
     * @param  string $code
     * @param  float $value
     * @param  float $baseValue
     * @param  string $label
     * @return void
     */
    protected function _addTotalToTotals(&$totals, $code, $value, $baseValue, $label)
    {
        if ($value == 0 && $baseValue == 0) {
            return;
        }
        $total = ['code' => $code, 'value' => $value, 'base_value' => $baseValue, 'label' => $label];
        $totals[] = $total;
    }

    /**
     * Get gift wrapping items price with tax processing
     *
     * @param \Magento\Framework\DataObject $item
     * @param float $price
     * @param bool $includeTax
     * @param null|\Magento\Customer\Model\Address $shippingAddress
     * @param null|\Magento\Customer\Model\Address $billingAddress
     * @param null|int $ctc
     * @param mixed $store
     * @param bool $roundPrice
     * @return float
     */
    public function getPrice(
        $item,
        $price,
        $includeTax = false,
        $shippingAddress = null,
        $billingAddress = null,
        $ctc = null,
        $store = null,
        $roundPrice = true
    ) {
        if (!$price) {
            return $price;
        }
        $store = $this->_storeManager->getStore($store);
        $taxClassKey = $item->getTaxClassKey();
        if ($taxClassKey && $includeTax) {
            $shippingAddressDataObject = null;
            if ($shippingAddress instanceof \Magento\Customer\Model\Address\AbstractAddress) {
                $shippingAddressDataObject = $shippingAddress->getDataModel();
            }

            $billingAddressDataObject = null;
            if ($billingAddress instanceof \Magento\Customer\Model\Address\AbstractAddress) {
                $billingAddressDataObject = $billingAddress->getDataModel();
            }

            /** @var \Magento\Tax\Api\Data\QuoteDetailsItemInterface $taxableItem */
            $taxableItem = $this->quoteDetailsItemFactory->create();
            $taxableItem->setQuantity(1)
                ->setCode(self::TAXABLE_ITEM_CODE)
                ->setTaxClassId($taxClassKey->getValue())
                ->setIsTaxIncluded(false)
                ->setType(self::TAXABLE_ITEM_TYPE)
                ->setTaxClassKey($taxClassKey)
                ->setUnitPrice($price);
            $quoteDetails = $this->quoteDetailsFactory->create();
            $quoteDetails->setShippingAddress($shippingAddressDataObject)
                ->setBillingAddress($billingAddressDataObject)
                ->setCustomerTaxClassId($ctc)
                ->setItems([$taxableItem]);

            if ($billingAddressDataObject) {
                $quoteDetails->setCustomerId($billingAddressDataObject->getCustomerId());
            } elseif ($shippingAddressDataObject) {
                $quoteDetails->setCustomerId($shippingAddressDataObject->getCustomerId());
            }

            $storeId = null;
            if ($store) {
                $storeId = $store->getId();
            }
            $taxDetails = $this->taxCalculationService->calculateTax($quoteDetails, $storeId, $roundPrice);
            $taxDetailsItems = $taxDetails->getItems();
            $taxDetailsItem = array_pop($taxDetailsItems);
            return $taxDetailsItem->getPriceInclTax();
        }
        return $this->priceCurrency->round($price);
    }
}
