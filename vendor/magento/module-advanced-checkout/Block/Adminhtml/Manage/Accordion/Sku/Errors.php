<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Add by SKU errors accordion
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Sku;

/**
 * @api
 * @since 100.0.2
 */
class Errors extends \Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\AbstractErrors
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @codeCoverageIgnore
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\AdvancedCheckout\Model\CartFactory $cartFactory
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\AdvancedCheckout\Model\CartFactory $cartFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_registry = $registry;
        parent::__construct($context, $cartFactory, $data);
    }

    /**
     * Returns url to configure item
     *
     * @return string
     */
    public function getConfigureUrl()
    {
        $customer = $this->_registry->registry('checkout_current_customer');
        $store = $this->_registry->registry('checkout_current_store');
        $params = ['customer' => $customer->getId(), 'store' => $store->getId()];
        return $this->getUrl('checkout/index/configureProductToAdd', $params);
    }

    /**
     * Retrieve additional JavaScript for error grid
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function getAdditionalJavascript()
    {
        $code = "addBySku.addErrorSourceGrid({htmlId: '{$this->getId()}', listType: '{$this->getListType()}'})";

        return "if (jQuery(document).data('addBySkuInited')) {\n" .
            $code .
            "} else {\n" .
            "jQuery(document).on('addBySku:inited', function () {" .
            $code .
            "});\n" .
            "}";
    }

    /**
     * Returns current store model
     *
     * @return \Magento\Store\Model\Store
     * @codeCoverageIgnore
     */
    public function getStore()
    {
        return $this->_registry->registry('checkout_current_store');
    }

    /**
     * Get title of button, that adds products to shopping cart
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getAddButtonTitle()
    {
        return __('Add to Shopping Cart');
    }
}
