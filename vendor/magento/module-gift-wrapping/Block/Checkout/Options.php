<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Block\Checkout;

use Magento\Tax\Api\Data\TaxClassKeyInterface;

/**
 * Gift wrapping checkout process options block
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Options extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    protected $_designCollection;

    /**
     * @var bool
     */
    protected $_giftWrappingAvailable = false;

    /**
     * Gift wrapping data
     *
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $_giftWrappingData = null;

    /**
     * @var \Magento\Checkout\Model\CartFactory
     */
    protected $_checkoutCartFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $_wrappingCollectionFactory;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * Checkout types for order level and item level
     *
     * @var array
     */
    protected $checkoutItems;

    /**
     * @var \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory
     */
    private $taxClassKeyFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Model\CartFactory $checkoutCartFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param array $checkoutItems
     * @param array $data
     * @param \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory|null $taxClassKeyFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\GiftWrapping\Helper\Data $giftWrappingData,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\CartFactory $checkoutCartFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $checkoutItems,
        array $data = [],
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyFactory = null
    ) {
        parent::__construct($context, $data);
        $this->pricingHelper = $pricingHelper;
        $this->_giftWrappingData = $giftWrappingData;
        $this->_wrappingCollectionFactory = $wrappingCollectionFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_checkoutCartFactory = $checkoutCartFactory;
        $this->productRepository = $productRepository;
        $this->checkoutItems = $checkoutItems;
        $this->_isScopePrivate = true;
        $this->taxClassKeyFactory = $taxClassKeyFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory::class);
    }

    /**
     * Gift wrapping collection
     *
     * @return \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection
     */
    public function getDesignCollection()
    {
        if ($this->_designCollection === null) {
            $store = $this->_storeManager->getStore();
            $this->_designCollection = $this->_wrappingCollectionFactory->create()->addStoreAttributesToResult(
                $store->getId()
            )->applyStatusFilter()->applyWebsiteFilter(
                $store->getWebsiteId()
            );
        }
        return $this->_designCollection;
    }

    /**
     * Select element for choosing gift wrapping design
     *
     * @return array
     */
    public function getDesignSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            [
                'id' => 'giftwrapping-<%- data._type_ %>-<%- data._id_ %>',
                'class' => 'select',
            ]
        )->setName(
            'giftwrapping[<%- data._type_ %>][<%- data._id_ %>][design]'
        )->setExtraParams(
            'data-addr-id="<%- data._blockId_ %>"'
        )->setOptions(
            $this->getDesignCollection()->toOptionArray()
        );
        return $select->getHtml();
    }

    /**
     * Get quote instance
     *
     * @return \Magento\Quote\Model\Quote
     * @codeCoverageIgnore
     */
    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * Calculate including tax price
     *
     * @param \Magento\Framework\DataObject $item
     * @param float $basePrice
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @param bool $includeTax
     * @return string
     */
    public function calculatePrice($item, $basePrice, $shippingAddress, $includeTax = false)
    {
        $billingAddress = $this->getQuote()->getBillingAddress();
        $taxClass = $this->_giftWrappingData->getWrappingTaxClass();
        $taxClassKey = $this->taxClassKeyFactory->create();
        $taxClassKey->setType(TaxClassKeyInterface::TYPE_ID);
        $taxClassKey->setValue($taxClass);
        $item->setTaxClassKey($taxClassKey);
        $item->setTaxClassId($taxClass);

        $price = $this->_giftWrappingData->getPrice($item, $basePrice, $includeTax, $shippingAddress, $billingAddress);
        return $this->pricingHelper->currency($price, true, false);
    }

    /**
     * Return gift wrapping designs info
     *
     * @return \Magento\Framework\DataObject
     */
    public function getDesignsInfo()
    {
        $data = [];
        /** @var $item \Magento\GiftWrapping\Model\Wrapping */
        foreach ($this->getDesignCollection()->getItems() as $item) {
            $temp = [];
            foreach ($this->getQuote()->getAllShippingAddresses() as $address) {
                $entityId = $this->getQuote()->getIsMultiShipping() ? $address->getId() : $this->getQuote()->getId();
                if ($this->getDisplayWrappingBothPrices()) {
                    $temp[$entityId]['price_incl_tax'] = $this->escapeHtml(
                        $this->calculatePrice(
                            $item,
                            $item->getBasePrice(),
                            $address,
                            true
                        )
                    );
                    $temp[$entityId]['price_excl_tax'] = $this->escapeHtml(
                        $this->calculatePrice($item, $item->getBasePrice(), $address)
                    );
                } else {
                    $temp[$entityId]['price'] = $this->escapeHtml(
                        $this->calculatePrice(
                            $item,
                            $item->getBasePrice(),
                            $address,
                            $this->getDisplayWrappingIncludeTaxPrice()
                        )
                    );
                }
            }
            $temp['path'] = $this->escapeUrl($item->getImageUrl());
            $data[$item->getId()] = $temp;
        }
        return new \Magento\Framework\DataObject($data);
    }

    /**
     * Prepare and return quote items info
     *
     * @return \Magento\Framework\DataObject
     */
    public function getItemsInfo()
    {
        $data = [];
        if ($this->getQuote()->getIsMultiShipping()) {
            foreach ($this->getQuote()->getAllShippingAddresses() as $address) {
                if ($address->getGwId() === null) {
                    $address->setGwId($address->getQuote()->getGwId());
                }
                $data['gw'][$address->getId()] = $address->getGwId();
                $this->_processItems($address->getAllItems(), $address, $data);
            }
        } else {
            $this->_processItems($this->getQuote()->getAllItems(), $this->getQuote()->getShippingAddress(), $data);
        }
        return new \Magento\Framework\DataObject($data);
    }

    /**
     * Provide variable name for input items
     *
     * @param string $level - 'order_level' and 'item_level' are expected
     * @throws \InvalidArgumentException
     * @return string|null
     */
    public function getCheckoutTypeVariable($level)
    {
        $checkoutType = $this->getQuote()->getIsMultiShipping() ? 'multishipping' : 'onepage';
        if (array_key_exists($level, $this->checkoutItems[$checkoutType])) {
            return $this->checkoutItems[$checkoutType][$level];
        }

        throw new \InvalidArgumentException('Invalid level: ' . $level);
    }

    /**
     * Process items
     *
     * @param array $items
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @param array $data
     * @return array
     */
    protected function _processItems($items, $shippingAddress, &$data)
    {
        /** @var $item \Magento\Quote\Model\Quote\Item */
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $allowed = $item->getProduct()->getGiftWrappingAvailable();
            if ($this->_giftWrappingData->isGiftWrappingAvailableForProduct($allowed) && !$item->getIsVirtual()) {
                $temp = [];
                if ($item->getGwId() === null) {
                    $item->setGwId($item->getQuoteItem()->getGwId());
                }
                $data['gw'][$item->getId()] = $item->getGwId();
                if ($price = $item->getProduct()->getGiftWrappingPrice()) {
                    if ($this->getDisplayWrappingBothPrices()) {
                        $temp['price_incl_tax'] = $this->escapeHtml(
                            $this->calculatePrice(
                                new \Magento\Framework\DataObject(),
                                $price,
                                $shippingAddress,
                                true
                            )
                        );
                        $temp['price_excl_tax'] = $this->escapeHtml(
                            $this->calculatePrice(
                                new \Magento\Framework\DataObject(),
                                $price,
                                $shippingAddress
                            )
                        );
                    } else {
                        $temp['price'] = $this->escapeHtml(
                            $this->calculatePrice(
                                new \Magento\Framework\DataObject(),
                                $price,
                                $shippingAddress,
                                $this->getDisplayWrappingIncludeTaxPrice()
                            )
                        );
                    }
                }
                $data[$item->getId()] = $temp;
            }
        }
        return $data;
    }

    /**
     * Prepare and return printed card info
     *
     * @return \Magento\Framework\DataObject
     */
    public function getCardInfo()
    {
        $data = [];
        if ($this->getAllowPrintedCard()) {
            $price = $this->_giftWrappingData->getPrintedCardPrice();
            foreach ($this->getQuote()->getAllShippingAddresses() as $address) {
                $entityId = $this->getQuote()->getIsMultiShipping() ? $address->getId() : $this->getQuote()->getId();

                if ($this->getDisplayCardBothPrices()) {
                    $data[$entityId]['price_incl_tax'] = $this->escapeHtml(
                        $this->calculatePrice(
                            new \Magento\Framework\DataObject(),
                            $price,
                            $address,
                            true
                        )
                    );
                    $data[$entityId]['price_excl_tax'] = $this->escapeHtml(
                        $this->calculatePrice(
                            new \Magento\Framework\DataObject(),
                            $price,
                            $address
                        )
                    );
                } else {
                    $data[$entityId]['price'] = $this->escapeHtml(
                        $this->calculatePrice(
                            new \Magento\Framework\DataObject(),
                            $price,
                            $address,
                            $this->getDisplayCardIncludeTaxPrice()
                        )
                    );
                }
            }
        }
        return new \Magento\Framework\DataObject($data);
    }

    /**
     * Check display both prices for gift wrapping
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getDisplayWrappingBothPrices()
    {
        return $this->_giftWrappingData->displayCartWrappingBothPrices();
    }

    /**
     * Check display both prices for printed card
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getDisplayCardBothPrices()
    {
        return $this->_giftWrappingData->displayCartCardBothPrices();
    }

    /**
     * Check display prices including tax for gift wrapping
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getDisplayWrappingIncludeTaxPrice()
    {
        return $this->_giftWrappingData->displayCartWrappingIncludeTaxPrice();
    }

    /**
     * Check display price including tax for printed card
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getDisplayCardIncludeTaxPrice()
    {
        return $this->_giftWrappingData->displayCartCardIncludeTaxPrice();
    }

    /**
     * Check allow printed card
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAllowPrintedCard()
    {
        return $this->_giftWrappingData->allowPrintedCard();
    }

    /**
     * Check allow gift receipt
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAllowGiftReceipt()
    {
        return $this->_giftWrappingData->allowGiftReceipt();
    }

    /**
     * Check allow gift wrapping on order level
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAllowForOrder()
    {
        return $this->_giftWrappingData->isGiftWrappingAvailableForOrder();
    }

    /**
     * Check allow gift wrapping on order items
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAllowForItems()
    {
        return $this->_giftWrappingData->isGiftWrappingAvailableForItems();
    }

    /**
     * Check allow gift wrapping for order
     *
     * @return bool
     */
    public function canDisplayGiftWrapping()
    {
        $cartItems = $this->_checkoutCartFactory->create()->getItems();
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($cartItems as $item) {
            $product = $item->getProduct();
            if ($product->getGiftWrappingAvailable()) {
                $this->_giftWrappingAvailable = true;
                continue;
            }
        }

        $canDisplay = $this->getAllowForOrder() ||
            $this->getAllowForItems() ||
            $this->getAllowPrintedCard() ||
            $this->getAllowGiftReceipt() ||
            $this->_giftWrappingAvailable;
        return $canDisplay;
    }

    /**
     * Determines if gift wrapping is available for any product in this checkout
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getGiftWrappingAvailable()
    {
        return $this->_giftWrappingAvailable;
    }

    /**
     * Get design collection count
     *
     * @return int
     */
    public function getDesignCollectionCount()
    {
        return count($this->getDesignCollection());
    }
}
