<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Customer\Checkout;

/**
 * Customer gift registry multishipping checkout block
 *
 * @api
 * @since 100.0.2
 */
class Multishipping extends \Magento\GiftRegistry\Block\Customer\Checkout
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
     * @param \Magento\Checkout\Model\Session $customerSession
     * @param \Magento\GiftRegistry\Model\EntityFactory $entityFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\GiftRegistry\Helper\Data $giftRegistryData,
        \Magento\Checkout\Model\Session $customerSession,
        \Magento\GiftRegistry\Model\EntityFactory $entityFactory,
        array $data = []
    ) {
        parent::__construct($context, $giftRegistryData, $customerSession, $entityFactory, $data);
    }

    /**
     * Get quote gift registry items
     *
     * @return array
     */
    public function getItems()
    {
        $items = [];
        foreach ($this->_getGiftRegistryQuoteItems() as $quoteItemId => $item) {
            if ($item['is_address']) {
                $items[$quoteItemId] = $item;
            }
        }
        return $items;
    }

    /**
     * Retrieve giftregistry selected addresses indexes
     *
     * @return array
     */
    public function getGiftregistrySelectedAddressesIndexes()
    {
        $result = [];
        if ($this->_getCheckoutSession()->getQuoteId()) {
            $registryQuoteItemIds = array_keys($this->getItems());
            $quoteAddressItems = $this->_getCheckoutSession()->getQuote()->getShippingAddressesItems();
            /** @var \Magento\Quote\Model\Quote\Item $quoteAddressItem */
            foreach ($quoteAddressItems as $index => $quoteAddressItem) {
                $quoteItemId = $quoteAddressItem->getQuoteItem()->getId();
                if (!$quoteAddressItem->getCustomerAddressId() && in_array($quoteItemId, $registryQuoteItemIds)) {
                    $result[] = $index;
                }
            }
        }
        return $result;
    }
}
