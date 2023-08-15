<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\CartFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftWrapping\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Framework\Pricing\Helper\Data as PricingData;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;

/**
 * Gift Wrapping config provider
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var CartFactory
     */
    protected $checkoutCartFactory;

    /**
     * @var ProductRepositoryInterface
     * @deprecated 101.0.0
     */
    protected $productRepository;

    /**
     * Gift wrapping data
     *
     * @var Data
     */
    protected $giftWrappingData = null;

    /**
     * @var bool
     */
    protected $giftWrappingAvailable = false;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    protected $designCollection;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $wrappingCollectionFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PricingData
     */
    protected $pricingHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Tax class key factory
     *
     * @var TaxClassKeyInterfaceFactory
     */
    protected $taxClassKeyFactory;

    /**
     * @param CartFactory $checkoutCartFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Data $giftWrappingData
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $wrappingCollectionFactory
     * @param UrlInterface $urlBuilder
     * @param Repository $assetRepo
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param CheckoutSession $checkoutSession
     * @param PricingData $pricingHelper
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param TaxClassKeyInterfaceFactory $taxClassKeyFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CartFactory $checkoutCartFactory,
        ProductRepositoryInterface $productRepository,
        Data $giftWrappingData,
        StoreManagerInterface $storeManager,
        CollectionFactory $wrappingCollectionFactory,
        UrlInterface $urlBuilder,
        Repository $assetRepo,
        RequestInterface $request,
        LoggerInterface $logger,
        CheckoutSession $checkoutSession,
        PricingData $pricingHelper,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyFactory
    ) {
        $this->checkoutCartFactory = $checkoutCartFactory;
        $this->productRepository = $productRepository;
        $this->giftWrappingData = $giftWrappingData;
        $this->storeManager = $storeManager;
        $this->wrappingCollectionFactory = $wrappingCollectionFactory;
        $this->urlBuilder = $urlBuilder;
        $this->assetRepo = $assetRepo;
        $this->request = $request;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->pricingHelper = $pricingHelper;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->taxClassKeyFactory = $taxClassKeyFactory;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        return [
            'giftWrapping' => [
                'canDisplayGiftWrapping' => $this->canDisplayGiftWrapping(),
                'designCollectionCount' => $this->getDesignCollectionCount(),
                'allowForOrder' => $this->getAllowForOrder(),
                'allowForItems' => $this->getAllowForItems(),
                'giftWrappingAvailable' => $this->giftWrappingAvailable,
                'spacerSrc' => $this->getViewFileUrl('images/spacer.gif'),
                'displayWrappingBothPrices' => $this->getDisplayWrappingBothPrices(),
                'displayCardBothPrices' => $this->getDisplayCardBothPrices(),
                'displayGiftWrappingInclTaxPrice' => $this->getDisplayWrappingIncludeTaxPrice(),
                'displayCardInclTaxPrice' => $this->getDisplayCardIncludeTaxPrice(),
                'designsInfo' => $this->getDesignsInfo(),
                'itemsInfo' => $this->getItemsInfo(),
                'isAllowPrintedCard' => $this->getAllowPrintedCard(),
                'isAllowGiftReceipt' => $this->getAllowGiftReceipt(),
                'cardInfo' => $this->getCardInfo(),
                'appliedWrapping' => $this->getAppliedWrapping(),
                'appliedPrintedCard' => $this->getQuote()->getGwAddCard(),
                'appliedGiftReceipt' => $this->getQuote()->getGwAllowGiftReceipt(),
            ],
            'quoteId' => $this->getQuoteMaskId()
        ];
    }

    /**
     * Return list of applied wrapping
     *
     * @return array
     */
    public function getAppliedWrapping()
    {
        $quote = $this->getQuote();
        $wrappings = [];
        $cartItems = $this->checkoutCartFactory->create()->getItems();
        if ($quote->hasGwId()) {
            $wrappings['orderLevel'] = $quote->getGwId();
        }
        foreach ($cartItems as $item) {
            if ($item->isDeleted()) {
                continue;
            }
            if (!$item->hasGwId()) {
                continue;
            }
            if (!isset($wrappings['itemLevel'])) {
                $wrappings['itemLevel'] = [];
            }
            $wrappings['itemLevel'][$item->getId()] = $item->getGwId();
        }

        return $wrappings;
    }

    /**
     * Return quote id mask
     *
     * @return string
     */
    public function getQuoteMaskId()
    {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->load($this->getQuote()->getId(), 'quote_id');
        return $quoteIdMask->getMaskedId();
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
            $price = $this->giftWrappingData->getPrintedCardPrice();
            foreach ($this->getQuote()->getAllShippingAddresses() as $address) {
                if ($this->getDisplayCardBothPrices()) {
                    $data['price_incl_tax'] = $this->calculatePrice(
                        new \Magento\Framework\DataObject(),
                        $price,
                        $address,
                        true
                    );
                    $data['price_excl_tax'] = $this->calculatePrice(
                        new \Magento\Framework\DataObject(),
                        $price,
                        $address
                    );
                } else {
                    $data['price'] = $this->calculatePrice(
                        new \Magento\Framework\DataObject(),
                        $price,
                        $address,
                        $this->getDisplayCardIncludeTaxPrice()
                    );
                }
            }
        }
        return $data;
    }

    /**
     * Check display both prices for printed card
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getDisplayCardBothPrices()
    {
        return $this->giftWrappingData->displayCartCardBothPrices();
    }

    /**
     * Check display price including tax for printed card
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getDisplayCardIncludeTaxPrice()
    {
        return $this->giftWrappingData->displayCartCardIncludeTaxPrice();
    }

    /**
     * Get quote instance
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
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
        $taxClassId = $this->giftWrappingData->getWrappingTaxClass();

        $taxClassKey = $this->taxClassKeyFactory->create();
        $taxClassKey->setType(TaxClassKeyInterface::TYPE_ID);
        $taxClassKey->setValue($taxClassId);

        $item->setTaxClassKey($taxClassKey);
        $price = $this->giftWrappingData->getPrice($item, $basePrice, $includeTax, $shippingAddress, $billingAddress);
        return $this->pricingHelper->currency($price, false) ?: 0;
    }

    /**
     * Return gift wrapping designs info
     *
     * @return array
     */
    public function getDesignsInfo()
    {
        $designInfo = [];
        /** @var $item \Magento\GiftWrapping\Model\Wrapping */
        foreach ($this->getDesignCollection()->getItems() as $item) {
            $design = [];
            foreach ($this->getQuote()->getAllShippingAddresses() as $address) {
                if ($this->getDisplayWrappingBothPrices()) {
                    $design['price_incl_tax'] = $this->calculatePrice(
                        $item,
                        $item->getBasePrice(),
                        $address,
                        true
                    );
                    $design['price_excl_tax'] = $this->calculatePrice($item, $item->getBasePrice(), $address);
                } else {
                    $design['price'] = $this->calculatePrice(
                        $item,
                        $item->getBasePrice(),
                        $address,
                        $this->getDisplayWrappingIncludeTaxPrice()
                    );
                }
                $design['path'] = $item->getImageUrl();
                $design['label'] = $item->getDesign();
            }
            $designInfo[$item->getId()] = $design;
        }

        return $designInfo;
    }

    /**
     * Prepare and return quote items info
     *
     * @return array
     */
    protected function getItemsInfo()
    {
        $itemsData = [];
        $shippingAddress = $this->getQuote()->getShippingAddress();

        /** @var \Magento\Quote\Model\ResourceModel\Quote\Item $quoteItem */
        foreach ($this->getQuote()->getAllItems() as $quoteItem) {
            $product = $quoteItem->getProduct();
            if ($quoteItem->getParentItem()) {
                continue;
            }
            // check if gift wrapping is available for the product (product settings must override system configuration)
            $isWrappingAvailable = (bool)$this->giftWrappingData->isGiftWrappingAvailableForProduct(
                $product->getGiftWrappingAvailable()
            );
            if (!$isWrappingAvailable) {
                $itemsData[$quoteItem->getId()] = ['is_available' => false];
                continue;
            }

            $price = $product->getGiftWrappingPrice();
            $wrappingConfig = [
                'is_available' => true,
            ];
            if ($price) {
                $item = new \Magento\Framework\DataObject();
                if ($this->getDisplayWrappingBothPrices()) {
                    $wrappingConfig['price_incl_tax'] = $this->calculatePrice($item, $price, $shippingAddress, true);
                    $wrappingConfig['price_excl_tax'] = $this->calculatePrice($item, $price, $shippingAddress);
                } else {
                    $wrappingConfig['price'] = $this->calculatePrice(
                        $item,
                        $price,
                        $shippingAddress,
                        $this->getDisplayWrappingIncludeTaxPrice()
                    );
                }
            }
            $itemsData[$quoteItem->getId()] = $wrappingConfig;
        }
        return $itemsData;
    }

    /**
     * Check display both prices for gift wrapping
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getDisplayWrappingBothPrices()
    {
        return $this->giftWrappingData->displayCartWrappingBothPrices();
    }

    /**
     * Check display prices including tax for gift wrapping
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getDisplayWrappingIncludeTaxPrice()
    {
        return $this->giftWrappingData->displayCartWrappingIncludeTaxPrice();
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    protected function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }

    /**
     * Check allow gift wrapping for order
     *
     * @return bool
     */
    public function canDisplayGiftWrapping()
    {
        $cartItems = $this->checkoutCartFactory->create()->getItems();
        /** @var  \Magento\Quote\Model\Quote\Item $item */
        foreach ($cartItems as $item) {
            if ($item->isDeleted()) {
                continue;
            }
            $product = $item->getProduct();
            if ($product->getGiftWrappingAvailable()) {
                $this->giftWrappingAvailable = true;
                continue;
            }
        }
        return $this->getAllowForOrder() ||
            $this->getAllowForItems() ||
            $this->getAllowPrintedCard() ||
            $this->getAllowGiftReceipt() ||
            $this->giftWrappingAvailable;
    }

    /**
     * Gift wrapping collection
     *
     * @return \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection
     */
    public function getDesignCollection()
    {
        if ($this->designCollection === null) {
            $store = $this->storeManager->getStore();
            $this->designCollection = $this->wrappingCollectionFactory->create();
            $this->designCollection->addStoreAttributesToResult($store->getId());
            $this->designCollection->applyStatusFilter();
            $this->designCollection->applyWebsiteFilter($store->getWebsiteId());
        }
        return $this->designCollection;
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

    /**
     * Check allow gift wrapping on order level
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAllowForOrder()
    {
        return $this->giftWrappingData->isGiftWrappingAvailableForOrder() && !$this->getQuote()->isVirtual();
    }

    /**
     * Check allow gift wrapping on order items
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAllowForItems()
    {
        return (bool)$this->giftWrappingData->isGiftWrappingAvailableForItems();
    }

    /**
     * Check allow printed card
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAllowPrintedCard()
    {
        return (bool)$this->giftWrappingData->allowPrintedCard();
    }

    /**
     * Check allow gift receipt
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAllowGiftReceipt()
    {
        return (bool)$this->giftWrappingData->allowGiftReceipt();
    }

    /**
     * Determines if gift wrapping is available for any product in this checkout
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getGiftWrappingAvailable()
    {
        return $this->giftWrappingAvailable;
    }
}
