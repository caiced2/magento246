<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Form for adding products by SKU
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Sales\Order\Create\Sku;

/**
 * @api
 * @since 100.0.2
 */
class Errors extends \Magento\AdvancedCheckout\Block\Adminhtml\Sku\Errors\AbstractErrors
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * @codeCoverageIgnore
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\AdvancedCheckout\Model\CartFactory $cartFactory
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\AdvancedCheckout\Model\CartFactory $cartFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        array $data = []
    ) {
        $this->_sessionQuote = $sessionQuote;
        parent::__construct($context, $cartFactory, $data);
    }

    /**
     * Returns url to configure item
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function getConfigureUrl()
    {
        return $this->getUrl('sales/order_create/configureProductToAdd');
    }

    /**
     * Returns enterprise cart model with custom session for order create page
     *
     * @return \Magento\AdvancedCheckout\Model\Cart
     */
    public function getCart()
    {
        if (!$this->_cart) {
            $this->_cart = parent::getCart()->setSession($this->_sessionQuote);
        }
        return $this->_cart;
    }

    /**
     * Returns current store model
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        $storeId = $this->getCart()->getSession()->getStoreId();
        return $this->_storeManager->getStore($storeId);
    }

    /**
     * Get title of button, that adds products to order
     *
     * @codeCoverageIgnore
     * @return \Magento\Framework\Phrase
     */
    public function getAddButtonTitle()
    {
        return __('Add Products to Order');
    }
}
