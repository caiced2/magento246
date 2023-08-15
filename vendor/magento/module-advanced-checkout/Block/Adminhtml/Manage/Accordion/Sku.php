<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion;

use Magento\Framework\Phrase;

/**
 * "Add by SKU" accordion
 *
 * @api
 * @codeCoverageIgnore
 * @method string getHeaderText()
 * @method \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Sku setHeaderText(Phrase $phrase)
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Sku extends \Magento\AdvancedCheckout\Block\Adminhtml\Sku\AbstractSku
{
    /**
     * Define accordion header
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setHeaderText(__('Add to Shopping Cart by SKU'));
    }

    /**
     * Register grid with instance of AdminCheckout, register new list type and URL to fetch configure popup HTML
     *
     * @return string
     */
    public function getAdditionalJavascript()
    {
        // Origin of configure popup HTML
        $js = $this->getJsOrderObject() .
            ".addSourceGrid({htmlId: \"{$this->getId()}\", " .
            "listType: \"{$this->getListType()}\"});";
        $js .= $this->getJsOrderObject() . ".addNoCleanSource('{$this->getId()}');";
        $js .= 'addBySku.observeAddToCart();';
        return $js;
    }

    /**
     * Retrieve JavaScript AdminCheckout instance name
     *
     * @return string
     */
    public function getJsOrderObject()
    {
        return 'checkoutObj';
    }

    /**
     * Retrieve container ID for error grid
     *
     * @return string
     */
    public function getErrorGridId()
    {
        return 'checkout_errors';
    }

    /**
     * Retrieve file upload URL
     *
     * @return string
     */
    public function getFileUploadUrl()
    {
        return $this->getUrl('checkout/index/uploadSkuCsv');
    }

    /**
     * Retrieve context specific JavaScript
     *
     * @return string
     */
    public function getContextSpecificJs()
    {
        return 'jQuery(initSku);';
    }
}
