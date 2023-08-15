<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model\Observer;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class UploadSkuCsv implements ObserverInterface
{
    /**
     * Checkout data
     *
     * @var Data
     */
    private $checkoutData;

    /**
     * @var CartProvider
     */
    private $cartProvider;

    /**
     * @param Data $checkoutHelper
     * @param CartProvider $backendCartProvider
     */
    public function __construct(
        Data $checkoutHelper,
        CartProvider $backendCartProvider
    ) {
        $this->checkoutData = $checkoutHelper;
        $this->cartProvider = $backendCartProvider;
    }

    /**
     * Upload and parse CSV file with SKUs
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var $helper Data */
        $helper = $this->checkoutData;
        $rows = $helper->isSkuFileUploaded($observer->getRequestModel()) ? $helper->processSkuFileUploading() : [];
        if (empty($rows)) {
            return;
        }

        /* @var $orderCreateModel \Magento\Sales\Model\AdminOrder\Create */
        $orderCreateModel = $observer->getOrderCreateModel();
        $quoteStore = $orderCreateModel->getSession()->getStore();
        /** @var \Magento\AdvancedCheckout\Model\Cart $cart */
        $cart = $this->cartProvider->get($observer);
        $cart->setCurrentStore($quoteStore);
        $cart->prepareAddProductsBySku($rows);
        $cart->saveAffectedProducts($orderCreateModel, false);
    }
}
