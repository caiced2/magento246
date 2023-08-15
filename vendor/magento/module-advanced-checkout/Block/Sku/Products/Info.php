<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Sku\Products;

use Magento\AdvancedCheckout\Helper\Data;

/**
 * SKU failed information Block
 *
 * @api
 * @method \Magento\Quote\Model\Quote\Item getItem()
 * @since 100.0.2
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * Checkout data
     *
     * @var \Magento\AdvancedCheckout\Helper\Data
     */
    protected $_checkoutData;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Product alert data
     *
     * @var \Magento\ProductAlert\Helper\Data
     */
    protected $_productAlertData;

    /**
     * @codeCoverageIgnore
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\ProductAlert\Helper\Data $productAlertData
     * @param \Magento\AdvancedCheckout\Helper\Data $checkoutData
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\ProductAlert\Helper\Data $productAlertData,
        Data $checkoutData,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_productAlertData = $productAlertData;
        $this->_checkoutData = $checkoutData;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * Retrieve item's message
     *
     * @return string
     */
    public function getMessage()
    {
        switch ($this->getItem()->getCode()) {
            case Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK:
                $message = '<span class="sku-out-of-stock" id="sku-stock-failed-'
                    . $this->getItem()->getId()
                    . '">'
                    . $this->_checkoutData->getMessage(Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK)
                    . '</span>';
                return $message;
            case Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED:
                $message = $this->_checkoutData->getMessage(Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED);
                $message .= '<br/>' . __(
                    "Only %1%2%3 left in stock",
                    '<span class="sku-failed-qty" id="sku-stock-failed-' . $this->getItem()->getId() . '">',
                    $this->getItem()->getQtyMaxAllowed(),
                    '</span>'
                );
                return $message;
            case Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART:
                $item = $this->getItem();
                $message = $this->_checkoutData->getMessage(Data::ADD_ITEM_STATUS_FAILED_QTY_ALLOWED_IN_CART);
                $message .= '<br />';
                if ($item->getQtyMaxAllowed()) {
                    $qtyMessage = '<span class="sku-failed-qty" id="sku-stock-failed-'
                        . $item->getId() . '">' . ($item->getQtyMaxAllowed()  * 1) . '</span>';
                    $message .= __('You can buy up to %1 of these per purchase.', $qtyMessage);
                } elseif ($item->getQtyMinAllowed()) {
                    $qtyMessage = '<span class="sku-failed-qty" id="sku-stock-failed-' . $item->getId() . '">'
                        . ($item->getQtyMinAllowed()  * 1) . '</span>';
                    $message .= __('You must buy at least %1 of these per purchase.', $qtyMessage);
                }
                return $message;
            default:
                $error = $this->_checkoutData->getMessage($this->getItem()->getCode());
                $error = $error ? $error : $this->escapeHtml($this->getItem()->getError());
                return $error ? $error : '';
        }
    }

    /**
     * Check whether item is 'SKU failed'
     *
     * @return bool
     */
    public function isItemSkuFailed()
    {
        return $this->getItem()->getCode() == Data::ADD_ITEM_STATUS_FAILED_SKU;
    }

    /**
     * Get not empty template only for failed items
     *
     * @return string
     */
    protected function _toHtml()
    {
        return $this->getItem()->getCode() ? parent::_toHtml() : '';
    }

    /**
     * Get configure/notification/other link
     *
     * @return string
     */
    public function getLink()
    {
        $item = $this->getItem();
        switch ($item->getCode()) {
            case Data::ADD_ITEM_STATUS_FAILED_CONFIGURE:
                $link = $this->getUrl(
                    'checkout/cart/configureFailed',
                    ['id' => $item->getProductId(), 'qty' => $item->getQty(), 'sku' => $item->getSku()]
                );
                return '<a href="' . $link . '" class="action configure">' . __(
                    "Specify the product's options."
                ) . '</a>';
            case Data::ADD_ITEM_STATUS_FAILED_OUT_OF_STOCK:
                /** @var $helper \Magento\ProductAlert\Helper\Data */
                $helper = $this->_productAlertData;

                if (!$helper->isStockAlertAllowed()) {
                    return '';
                }

                $helper->setProduct($this->getItem()->getProduct());
                $signUpLabel = $this->escapeHtml(__('Alert me when this item is available.'));
                return '<a href="' . $this->escapeHtml(
                    $helper->getSaveUrl('stock')
                ) . '" title="' . $signUpLabel . '">' . $signUpLabel . '</a>';
            default:
                return '';
        }
    }

    /**
     * Get tier price formatted with html
     *
     * @return string
     */
    public function getProductTierPriceHtml()
    {
        $priceRender = $this->getPriceRender();

        $price = '';
        if ($priceRender) {
            $price = $priceRender->render(
                \Magento\Catalog\Pricing\Price\TierPrice::PRICE_CODE,
                $this->getItem()->getProduct(),
                [
                    'include_container' => true,
                    'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST
                ]
            );
        }

        return $price;
    }

    /**
     * @codeCoverageIgnore
     * @return \Magento\Framework\Pricing\Render
     */
    protected function getPriceRender()
    {
        return $this->getLayout()->getBlock('product.price.render.default');
    }
}
