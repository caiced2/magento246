<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage;

use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Helper\Data;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Shopping Cart items grid
 *
 * @api
 * @author     Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Items extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $_taxConfig;

    /**
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    protected $_wishlistFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @codeCoverageIgnore
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     * @param Data|null $catalogHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        PriceCurrencyInterface $priceCurrency,
        array $data = [],
        Data $catalogHelper = null
    ) {
        $data['catalogHelper'] = $catalogHelper ?: ObjectManager::getInstance()->get(Data::class);
        $this->_taxConfig = $taxConfig;
        $this->_registry = $registry;
        parent::__construct($context, $data);
        $this->_wishlistFactory = $wishlistFactory;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Retrieve grid id in template
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getJsObjectName()
    {
        return 'checkoutItemsGrid';
    }

    /**
     * Prepare items collection
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Item[]
     * @codeCoverageIgnore
     */
    public function getItems()
    {
        return $this->getQuote()->getAllVisibleItems();
    }

    /**
     * Return current customer id
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getCustomerId()
    {
        return $this->getCustomer()->getId();
    }

    /**
     * Check if we need display grid totals include tax
     *
     * @return bool
     */
    public function displayTotalsIncludeTax()
    {
        $res = $this->_taxConfig->displayCartSubtotalInclTax(
            $this->getStore()
        ) || $this->_taxConfig->displayCartSubtotalBoth(
            $this->getStore()
        );

        return $res;
    }

    /**
     * Return quote subtotal
     *
     * @return float
     */
    public function getSubtotal()
    {
        if ($this->getQuote()->isVirtual()) {
            $address = $this->getQuote()->getBillingAddress();
        } else {
            $address = $this->getQuote()->getShippingAddress();
        }
        if ($this->displayTotalsIncludeTax()) {
            return $address->getSubtotal() + $address->getTaxAmount();
        } else {
            return $address->getSubtotal();
        }
    }

    /**
     * Return quote subtotal with discount applied
     *
     * @return float
     */
    public function getSubtotalWithDiscount()
    {
        $address = $this->getQuote()->getShippingAddress();
        if ($this->displayTotalsIncludeTax()) {
            return $address->getSubtotal() + $address->getTaxAmount() + $this->getDiscountAmount();
        } else {
            return $address->getSubtotal() + $this->getDiscountAmount();
        }
    }

    /**
     * Return quote discount
     *
     * @codeCoverageIgnore
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->getQuote()->getShippingAddress()->getDiscountAmount();
    }

    /**
     * Return formatted price
     *
     * @param float $value
     * @return string
     * @codeCoverageIgnore
     */
    public function formatPrice($value)
    {
        return $this->priceCurrency->format(
            $value,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->getStore()
        );
    }

    /**
     * Check whether to use custom price for item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function usedCustomPriceForItem($item)
    {
        return false;
    }

    /**
     * ACL limitations
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isAllowedActionColumn()
    {
        return $this->_authorization->isAllowed('Magento_AdvancedCheckout::update');
    }

    /**
     * Return current quote from registry
     *
     * @return \Magento\Quote\Model\Quote
     * @codeCoverageIgnore
     */
    protected function getQuote()
    {
        return $this->_registry->registry('checkout_current_quote');
    }

    /**
     * Return current store from registry
     *
     * @return \Magento\Store\Model\Store
     * @codeCoverageIgnore
     */
    public function getStore()
    {
        return $this->_registry->registry('checkout_current_store');
    }

    /**
     * Return current customer from registry
     *
     * @return \Magento\Customer\Model\Customer
     * @codeCoverageIgnore
     */
    public function getCustomer()
    {
        return $this->_registry->registry('checkout_current_customer');
    }

    /**
     * Generate configure button html
     *
     * @param  \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    public function getConfigureButtonHtml($item)
    {
        $product = $item->getProduct();
        if ($product->canConfigure()) {
            $class = '';
            $addAttributes = sprintf('onclick="checkoutObj.showQuoteItemConfiguration(%s)"', $item->getId());
        } else {
            $class = 'disabled';
            $addAttributes = 'disabled="disabled"';
        }
        return sprintf(
            '<button type="button" class="scalable %s" %s><span><span><span>%s</span></span></span></button>',
            $class,
            $addAttributes,
            __('Configure')
        );
    }

    /**
     * Returns whether moving to wishlist is allowed for this item
     *
     * @codeCoverageIgnore
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function isMoveToWishlistAllowed($item)
    {
        return $item->getProduct()->isVisibleInSiteVisibility();
    }

    /**
     * Retrieve collection of customer wishlists
     *
     * @return \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection
     * @codeCoverageIgnore
     */
    public function getCustomerWishlists()
    {
        /* @var \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $wishlistCollection */
        return $this->_wishlistFactory->create()->getCollection()->filterByCustomerId($this->getCustomerId());
    }

    /**
     * Get the item unit price html
     *
     * @param Item $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getItemUnitPriceHtml(Item $item)
    {
        $block = $this->getLayout()->getBlock('item_unit_price');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Get the item row total html
     *
     * @param Item $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getItemRowTotalHtml(Item $item)
    {
        $block = $this->getLayout()->getBlock('item_row_total');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Return html for row total with discount
     *
     * @param Item $item
     * @return string
     * @codeCoverageIgnore
     */
    public function getItemRowTotalWithDiscountHtml(Item $item)
    {
        $block = $this->getLayout()->getBlock('item_row_total_with_discount');
        $block->setItem($item);
        return $block->toHtml();
    }
}
