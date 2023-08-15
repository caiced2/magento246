<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create;

use Magento\Payment\Model\Method\Free;

/**
 * Customer balance block for order creation page
 *
 * @api
 * @since 100.0.2
 */
class Payment extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\CustomerBalance\Model\Balance
     */
    protected $_balanceInstance;

    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    protected $_orderCreate;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceHelper;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceHelper
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context   $context,
        \Magento\CustomerBalance\Model\BalanceFactory      $balanceFactory,
        \Magento\Backend\Model\Session\Quote               $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create             $orderCreate,
        \Magento\CustomerBalance\Helper\Data               $customerBalanceHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface  $priceCurrency,
        array                                              $data = []
    ) {
        $this->_balanceFactory = $balanceFactory;
        $this->_sessionQuote = $sessionQuote;
        $this->_orderCreate = $orderCreate;
        $this->_customerBalanceHelper = $customerBalanceHelper;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order create model
     *
     * @return \Magento\Sales\Model\AdminOrder\Create
     */
    protected function _getOrderCreateModel()
    {
        return $this->_orderCreate;
    }

    /**
     * Return store manager instance
     *
     * @return \Magento\Store\Model\StoreManagerInterface
     */
    protected function _getStoreManagerModel()
    {
        return $this->_storeManager;
    }

    /**
     * Format value as price
     *
     * @param float $value
     * @return string
     */
    public function formatPrice($value)
    {
        return $this->priceCurrency->format(
            $value,
            true,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->_sessionQuote->getStore()
        );
    }

    /**
     * Balance getter
     *
     * @param bool $convertPrice
     * @return float
     */
    public function getBalance($convertPrice = false)
    {
        if (!$this->_customerBalanceHelper->isEnabled() || !$this->_getBalanceInstance()) {
            return 0.0;
        }
        if ($convertPrice) {
            return $this->priceCurrency->convert(
                $this->_getBalanceInstance()->getAmount(),
                $this->_getOrderCreateModel()->getQuote()->getStoreId()
            );
        }
        return $this->_getBalanceInstance()->getAmount();
    }

    /**
     * Check whether quote uses customer balance
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseCustomerBalance()
    {
        return $this->_orderCreate->getQuote()->getUseCustomerBalance();
    }

    /**
     * Check whether customer balance fully covers quote
     *
     * @return bool
     */
    public function isFullyPaid()
    {
        if (!$this->_getBalanceInstance()) {
            return false;
        }
        return $this->_getBalanceInstance()->isFullAmountCovered($this->_orderCreate->getQuote());
    }

    /**
     * Check whether quote uses customer balance
     *
     * @return bool
     */
    public function isUsed()
    {
        return $this->getUseCustomerBalance();
    }

    /**
     * Instantiate/load balance and return it
     *
     * @return \Magento\CustomerBalance\Model\Balance|false
     */
    protected function _getBalanceInstance()
    {
        if (!$this->_balanceInstance) {
            $quote = $this->_orderCreate->getQuote();
            if (!$quote || !$quote->getCustomerId() || !$quote->getStoreId()) {
                return false;
            }

            $store = $this->_storeManager->getStore($quote->getStoreId());
            $this->_balanceInstance = $this->_balanceFactory->create()->setCustomerId(
                $quote->getCustomerId()
            )->setWebsiteId(
                $store->getWebsiteId()
            )->loadByCustomer();
        }
        return $this->_balanceInstance;
    }

    /**
     * Whether customer store credit balance could be used
     *
     * @return bool
     */
    public function canUseCustomerBalance()
    {
        if (!$this->_scopeConfig->isSetFlag(Free::XML_PATH_PAYMENT_FREE_ACTIVE)) {
            return false;
        }

        $quote = $this->_orderCreate->getQuote();
        return $this->getBalance() && ($quote->getBaseGrandTotal() + $quote->getBaseCustomerBalAmountUsed() > 0);
    }
}
