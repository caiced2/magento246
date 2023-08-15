<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * GiftWrapping total calculator for quote
 */
namespace Magento\GiftWrapping\Model\Total\Quote;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address;

class Giftwrapping extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_store;

    /**
     * @var \Magento\Quote\Model\Quote|\Magento\Quote\Model\Quote\Address
     */
    protected $_quoteEntity;

    /**
     * Gift wrapping data
     *
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $_giftWrappingData = null;

    /**
     * @var \Magento\GiftWrapping\Model\WrappingFactory
     */
    protected $_wrappingFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     * @param \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\GiftWrapping\Helper\Data $giftWrappingData,
        \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_giftWrappingData = $giftWrappingData;
        $this->_wrappingFactory = $wrappingFactory;
        $this->setCode('giftwrapping');
    }

    /**
     * Collect gift wrapping totals
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        if ($shippingAssignment->getShipping()->getAddress()->getAddressType() !== Address::TYPE_SHIPPING) {
            return $this;
        }

        $this->_store = $quote->getStore();
        if ($quote->getIsMultiShipping()) {
            $this->_quoteEntity = $shippingAssignment->getShipping()->getAddress();
        } else {
            $this->_quoteEntity = $quote;
        }

        $total = $this->_collectWrappingForItems($shippingAssignment, $total);
        $total = $this->_collectWrappingForQuote($total);
        $total = $this->_collectPrintedCard($total);

        $total->setBaseGrandTotal(
            $total->getBaseGrandTotal() +
            $total->getGwItemsBasePrice() +
            $total->getGwBasePrice() +
            $total->getGwCardBasePrice()
        );
        $total->setGrandTotal(
            $total->getGrandTotal() +
            $total->getGwItemsPrice() +
            $total->getGwPrice() +
            $total->getGwCardPrice()
        );

        $total->setGwAllowGiftReceipt($quote->getGwAllowGiftReceipt());

        $quote->setGwItemsBasePrice(0);
        $quote->setGwItemsPrice(0);
        $quote->setGwBasePrice(0);
        $quote->setGwPrice(0);
        $quote->setGwCardBasePrice(0);
        $quote->setGwCardPrice(0);

        $quote->setGwItemsBasePrice($total->getGwItemsBasePrice() + $quote->getGwItemsBasePrice());
        $quote->setGwItemsPrice($total->getGwItemsPrice() + $quote->getGwItemsPrice());
        $quote->setGwBasePrice($total->getGwBasePrice() + $quote->getGwBasePrice());
        $quote->setGwPrice($total->getGwPrice() + $quote->getGwPrice());
        $quote->setGwCardBasePrice($total->getGwCardBasePrice() + $quote->getGwCardBasePrice());
        $quote->setGwCardPrice($total->getGwCardPrice() + $quote->getGwCardPrice());

        return $this;
    }

    /**
     * Collect wrapping total for items
     *
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return \Magento\Quote\Model\Quote\Address\Total $total
     */
    protected function _collectWrappingForItems($shippingAssignment, $total)
    {
        $items = $shippingAssignment->getItems();
        $wrappingForItemsBaseTotal = false;
        $wrappingForItemsTotal = false;
        $itemGwIds = [];

        foreach ($items as $item) {
            if ($item->getProduct()->isVirtual() || $item->getParentItem() || !$item->getGwId()) {
                continue;
            }
            if ($item->getProduct()->getGiftWrappingPrice()) {
                $wrappingBasePrice = $item->getProduct()->getGiftWrappingPrice();
            } else {
                $wrapping = $this->_getWrapping($item->getGwId(), $this->_store);
                $wrappingBasePrice = $wrapping->getBasePrice();
            }
            $wrappingPrice = $this->priceCurrency->convert($wrappingBasePrice, $this->_store);
            $item->setGwBasePrice($wrappingBasePrice);
            $item->setGwPrice($wrappingPrice);
            $wrappingForItemsBaseTotal += $wrappingBasePrice * $item->getQty();
            $wrappingForItemsTotal += $wrappingPrice * $item->getQty();
        }
        $total->setGwItemsBasePrice($wrappingForItemsBaseTotal);
        $total->setGwItemsPrice($wrappingForItemsTotal);
        $total->setGwItemIds($itemGwIds);

        return $total;
    }

    /**
     * Collect wrapping total for quote
     *
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return \Magento\Quote\Model\Quote\Address\Total $total
     */
    protected function _collectWrappingForQuote($total)
    {
        $wrappingBasePrice = false;
        $wrappingPrice = false;
        if ($this->_quoteEntity->getGwId()) {
            $wrapping = $this->_getWrapping($this->_quoteEntity->getGwId(), $this->_store);
            $wrappingBasePrice = $wrapping->getBasePrice();
            $wrappingPrice = $this->priceCurrency->convert($wrappingBasePrice, $this->_store);
            $total->setGwId($this->_quoteEntity->getGwId());
        }
        $total->setGwBasePrice($wrappingBasePrice);
        $total->setGwPrice($wrappingPrice);
        return $total;
    }

    /**
     * Collect printed card total for quote
     *
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return \Magento\Quote\Model\Quote\Address\Total $total
     */
    protected function _collectPrintedCard($total)
    {
        $printedCardBasePrice = false;
        $printedCardPrice = false;
        $addCard = $this->_quoteEntity->getGwAddCard();
        if ($addCard) {
            $printedCardBasePrice = $this->_giftWrappingData->getPrintedCardPrice($this->_store);
            $printedCardPrice = $this->priceCurrency->convert($printedCardBasePrice, $this->_store);
        }
        $total->setGwCardBasePrice($printedCardBasePrice);
        $total->setGwCardPrice($printedCardPrice);
        $total->setGwAddCard($addCard);
        return $total;
    }

    /**
     * Return wrapping model for wrapping ID
     *
     * @param  int $wrappingId
     * @param  \Magento\Store\Model\Store $store
     * @return \Magento\GiftWrapping\Model\Wrapping
     */
    protected function _getWrapping($wrappingId, $store)
    {
        /** @var \Magento\GiftWrapping\Model\Wrapping $wrapping */
        $wrapping = $this->_wrappingFactory->create();
        $wrapping->setStoreId($store->getId());
        $wrapping->load($wrappingId);
        return $wrapping;
    }

    /**
     * Assign wrapping totals and labels to address object
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $itemGwIds = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getGwId()) {
                $itemGwIds[] = [
                    'item_id' => $item->getItemId(),
                    'gw_id' => $item->getGwId(),
                ];
            }
        }
        return [
            'code' => $this->getCode(),
            'title' => __('Gift Wrapping'),
            'gw_price' => $total->getGwPrice(),
            'gw_base_price' => $total->getGwBasePrice(),
            'gw_items_price' => $total->getGwItemsPrice(),
            'gw_items_base_price' => $total->getGwItemsBasePrice(),
            'gw_card_price' => $total->getGwCardPrice(),
            'gw_card_base_price' => $total->getGwCardBasePrice(),
            'gw_item_ids' => $itemGwIds,
            'gw_id' => $total->getGwId(),
            'gw_allow_gift_receipt' => $total->getGwAllowGiftReceipt(),
            'gw_add_card' => $total->getGwAddCard()
        ];
    }
}
