<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model;

/**
 * Admin Checkout processing model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Observer
{
    /**
     * Checkout data
     *
     * @var \Magento\AdvancedCheckout\Helper\Data
     */
    protected $_checkoutData;

    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $_addressFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param Cart $cart
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param \Magento\AdvancedCheckout\Helper\Data $checkoutData
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\Quote\AddressFactory $addressFactory
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Quote\Model\Quote $quote,
        Cart $cart,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\AdvancedCheckout\Helper\Data $checkoutData,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\Quote\AddressFactory $addressFactory
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_quote = $quote;
        $this->_cart = $cart;
        $this->_checkoutData = $checkoutData;
        $this->_quoteFactory = $quoteFactory;
        $this->_addressFactory = $addressFactory;
    }

    /**
     * Returns cart model for backend
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return Cart
     */
    protected function _getBackendCart(\Magento\Framework\Event\Observer $observer)
    {
        $storeId = $observer->getRequestModel()->getParam('storeId');
        if ($storeId === null || $storeId === '') {
            $storeId = $observer->getRequestModel()->getParam('store_id');

            if ($storeId === null || $storeId === '') {
                $storeId = $observer->getSession()->getStoreId();
            }
        }
        return $this->_cart->setSession(
            $observer->getSession()
        )->setContext(
            Cart::CONTEXT_ADMIN_ORDER
        )->setCurrentStore(
            (int)$storeId
        );
    }

    /**
     * Check submitted SKU's form the form or from error grid
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addBySku(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $request \Magento\Framework\App\RequestInterface */
        $request = $observer->getRequestModel();
        $cart = $this->_getBackendCart($observer);

        if (empty($request) || empty($cart)) {
            return;
        }

        $removeFailed = $request->getPost('sku_remove_failed');

        if ($removeFailed || $request->getPost('from_error_grid')) {
            $cart->removeAllAffectedItems();
            if ($removeFailed) {
                return;
            }
        }

        $sku = $observer->getRequestModel()->getPost('remove_sku', false);

        if ($sku) {
            $this->_getBackendCart($observer)->removeAffectedItem($sku);
            return;
        }

        $addBySkuItems = $request->getPost(
            \Magento\AdvancedCheckout\Block\Adminhtml\Sku\AbstractSku::LIST_TYPE,
            []
        );
        $items = $request->getPost('item', []);
        if (!$addBySkuItems) {
            return;
        }
        foreach ($addBySkuItems as $id => $params) {
            $sku = (string) (isset($params['sku']) ? $params['sku'] : $id);
            $cart->prepareAddProductBySku($sku, $params['qty'], isset($items[$id]) ? $items[$id] : []);
        }
        /* @var $orderCreateModel \Magento\Sales\Model\AdminOrder\Create */
        $orderCreateModel = $observer->getOrderCreateModel();
        $cart->saveAffectedProducts($orderCreateModel, false);
        // We have already saved succeeded add by SKU items in saveAffectedItems(). This prevents from duplicate saving.
        $request->setPostValue('item', []);
    }

    /**
     * Upload and parse CSV file with SKUs
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function uploadSkuCsv(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $helper \Magento\AdvancedCheckout\Helper\Data */
        $helper = $this->_checkoutData;
        $rows = $helper->isSkuFileUploaded(
            $observer->getRequestModel()
        ) ? $helper->processSkuFileUploading() : [];
        if (empty($rows)) {
            return;
        }

        /* @var $orderCreateModel \Magento\Sales\Model\AdminOrder\Create */
        $orderCreateModel = $observer->getOrderCreateModel();
        $cart = $this->_getBackendCart($observer);
        $cart->prepareAddProductsBySku($rows);
        $cart->saveAffectedProducts($orderCreateModel, false);
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
     * Calculate failed items quote-related data
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collectTotalsFailedItems(\Magento\Framework\Event\Observer $observer)
    {
        $affectedItems = $this->_cart->getFailedItems();
        if (empty($affectedItems)) {
            return;
        }

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

    /**
     * Add link to cart in cart sidebar to view grid with failed products
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function addCartLink($observer)
    {
        $block = $observer->getEvent()->getBlock();
        if (!$block instanceof \Magento\Checkout\Block\Cart\Sidebar) {
            return;
        }

        $failedItemsCount = count($this->_cart->getFailedItems());
        if ($failedItemsCount > 0) {
            $block->setAllowCartLink(true);
            $block->setCartEmptyMessage(__('%1 item(s) need your attention.', $failedItemsCount));
        }
    }
}
