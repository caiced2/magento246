<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model;

class FailedItemProcessor
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $_addressFactory;

    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Checkout data
     *
     * @var \Magento\AdvancedCheckout\Helper\Data
     */
    protected $_checkoutData;

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param \Magento\AdvancedCheckout\Helper\Data $checkoutData
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\Quote\AddressFactory $addressFactory
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\AdvancedCheckout\Helper\Data $checkoutData,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\Quote\AddressFactory $addressFactory
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_quote = $quote;
        $this->_checkoutData = $checkoutData;
        $this->_quoteFactory = $quoteFactory;
        $this->_addressFactory = $addressFactory;
    }

    /**
     * Copy real address to the quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address $realAddress
     * @return \Magento\Quote\Model\Quote\Address
     */
    protected function _copyAddress($quote, $realAddress)
    {
        $address = $this->_addressFactory->create();
        $address->setData($realAddress->getData());
        $address->setId(
            null
        )->unsEntityId()->unsetData(
            'cached_items_all'
        )->setQuote(
            $quote
        );
        return $address;
    }

    /**
     * Process failed items
     * @return void
     */
    public function process()
    {
        /** @var $quote \Magento\Quote\Model\Quote */
        $quote = $this->_quoteFactory->create();
        $collection = $this->_collectionFactory->create();

        foreach ($this->_checkoutData->getFailedItems(false) as $item) {
            /** @var $item \Magento\Quote\Model\Quote\Item */
            if ((double)$item->getQty() <= 0) {
                $item->setSkuRequestedQty($item->getQty());
                $item->setData('qty', 1);
            }
            $item->setQuote($quote);
            $collection->addItem($item);
        }

        $quote->preventSaving()->setItemsCollection($collection);

        $quote->setShippingAddress($this->_copyAddress($quote, $this->_quote->getShippingAddress()));
        $quote->setBillingAddress($this->_copyAddress($quote, $this->_quote->getBillingAddress()));
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        foreach ($quote->getAllItems() as $item) {
            /** @var $item \Magento\Quote\Model\Quote\Item */
            if ($item->hasSkuRequestedQty()) {
                $item->setData('qty', $item->getSkuRequestedQty());
            }
        }
    }
}
