<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Block\Adminhtml\Order\Create;

use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Gift wrapping order create abstract block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class AbstractCreate extends \Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate
{
    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    protected $_designCollection;

    /**
     * Gift wrapping data
     *
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $_giftWrappingData;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $_wrappingCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     * @param \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\GiftWrapping\Helper\Data $giftWrappingData,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        array $data = []
    ) {
        $this->_giftWrappingData = $giftWrappingData;
        $this->_wrappingCollectionFactory = $wrappingCollectionFactory;
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $data);
    }

    /**
     * Gift wrapping collection
     *
     * @return \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection
     */
    public function getDesignCollection()
    {
        if ($this->_designCollection === null) {
            $this->_designCollection = $this->_wrappingCollectionFactory->create()->addStoreAttributesToResult(
                $this->getStore()->getId()
            )->applyStatusFilter()->applyWebsiteFilter(
                $this->getStore()->getWebsiteId()
            );
        }
        return $this->_designCollection;
    }

    /**
     * Return gift wrapping designs info
     *
     * @return \Magento\Framework\DataObject
     */
    public function getDesignsInfo()
    {
        $data = [];
        foreach ($this->getDesignCollection()->getItems() as $item) {
            if ($this->getDisplayWrappingBothPrices()) {
                $temp['price_incl_tax'] = $this->escapeHtml(
                    $this->calculatePrice($item, $item->getBasePrice(), true)
                );
                $temp['price_excl_tax'] = $this->escapeHtml(
                    $this->calculatePrice($item, $item->getBasePrice())
                );
            } else {
                $temp['price'] = $this->escapeHtml(
                    $this->calculatePrice($item, $item->getBasePrice(), $this->getDisplayWrappingPriceInclTax())
                );
            }
            $temp['path'] = $this->escapeUrl($item->getImageUrl());
            $temp['design'] = $this->escapeHtml($item->getDesign());
            $data[$item->getId()] = $temp;
        }
        return new \Magento\Framework\DataObject($data);
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
            $price = $this->_giftWrappingData->getPrintedCardPrice($this->getStoreId());
            if ($this->getDisplayCardBothPrices()) {
                $data['price_incl_tax'] = $this->escapeHtml(
                    $this->calculatePrice(new \Magento\Framework\DataObject(), $price, true)
                );
                $data['price_excl_tax'] = $this->escapeHtml(
                    $this->calculatePrice(new \Magento\Framework\DataObject(), $price)
                );
            } else {
                $data['price'] = $this->escapeHtml(
                    $this->calculatePrice(
                        new \Magento\Framework\DataObject(),
                        $price,
                        $this->getDisplayCardPriceInclTax()
                    )
                );
            }
        }
        return new \Magento\Framework\DataObject($data);
    }

    /**
     * Calculate price
     *
     * @param \Magento\Framework\DataObject $item
     * @param float $basePrice
     * @param bool $includeTax
     * @return string
     */
    public function calculatePrice($item, $basePrice, $includeTax = false)
    {
        $shippingAddress = $this->getQuote()->getShippingAddress();
        $billingAddress = $this->getQuote()->getBillingAddress();

        $taxClass = $this->_giftWrappingData->getWrappingTaxClass($this->getStoreId());
        $item->setTaxClassId($taxClass);

        $price = $this->_giftWrappingData->getPrice($item, $basePrice, $includeTax, $shippingAddress, $billingAddress);
        return $this->priceCurrency->convertAndFormat($price, false);
    }

    /**
     * Check ability to display both prices for gift wrapping in shopping cart
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @codeCoverageIgnore
     */
    public function getDisplayWrappingBothPrices()
    {
        return $this->_giftWrappingData->displayCartWrappingBothPrices($this->getStoreId());
    }

    /**
     * Check ability to display prices including tax for gift wrapping in shopping cart
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @codeCoverageIgnore
     */
    public function getDisplayWrappingPriceInclTax()
    {
        return $this->_giftWrappingData->displayCartWrappingIncludeTaxPrice($this->getStoreId());
    }

    /**
     * Return quote id
     *
     * @return array|null
     * @codeCoverageIgnore
     */
    public function getEntityId()
    {
        return $this->getQuote()->getId();
    }
}
