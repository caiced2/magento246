<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Controller\Adminhtml;

use Magento\Framework\Exception\InputException;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;

/**
 * Admin Checkout index controller
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Index extends \Magento\Backend\App\Action
{
    /**
     * Flag that indicates whether page must be reloaded with correct params or not
     *
     * @var bool
     */
    protected $_redirectFlag = false;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry = null;

    /**
     * Customer factory
     *
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @codeCoverageIgnore
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
    ) {
        parent::__construct($context);
        $this->_registry = $registry;
        $this->customerFactory = $customerFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * Return Checkout model as singleton
     *
     * @return \Magento\AdvancedCheckout\Model\Cart
     */
    public function getCartModel()
    {
        return $this->_objectManager->get(
            \Magento\AdvancedCheckout\Model\Cart::class
        )->setSession(
            $this->_objectManager->get(\Magento\Backend\Model\Session::class)
        )->setContext(
            \Magento\AdvancedCheckout\Model\Cart::CONTEXT_ADMIN_CHECKOUT
        )->setCurrentStore(
            $this->getRequest()->getPost('store')
        );
    }

    /**
     * Init store based on quote and customer sharing options
     * Store customer, store and quote to registry
     *
     * @param bool $useRedirects
     *
     * @return $this
     * @throws InputException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _initData($useRedirects = true)
    {
        $customerId = $this->getRequest()->getParam('customer');
        $customer = $this->_objectManager->create(\Magento\Customer\Model\Customer::class)->load($customerId);
        if (!$customer->getId()) {
            throw new InputException(__("This customer couldn't be found. Verify the customer and try again."));
        }

        $storeManager = $this->_objectManager->get(\Magento\Store\Model\StoreManager::class);
        if ($storeManager->getStore(
            \Magento\Store\Model\Store::ADMIN_CODE
        )->getWebsiteId() == $customer->getWebsiteId()
        ) {
            if ($useRedirects) {
                $this->messageManager->addError(__('Shopping cart management is disabled for this customer.'));
                $this->_redirect('customer/index/edit', ['id' => $customerId]);
                $this->_redirectFlag = true;
                return $this;
            } else {
                throw new InputException(__('Shopping cart management is disabled for this customer.'));
            }
        }

        $cart = $this->getCartModel();
        $cart->setCustomer($customer);

        $storeId = $this->getRequest()->getParam('store');

        if ($storeId === null || $storeId == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            $storeId = $cart->getPreferredStoreId();
            if ($storeId && $useRedirects) {
                // Redirect to preferred store view
                if ($this->getRequest()->getQuery('isAjax', false) || $this->getRequest()->getQuery('ajax', false)) {
                    $this->getResponse()->representJson(
                        $this->_objectManager->get(
                            \Magento\Framework\Json\Helper\Data::class
                        )->jsonEncode(
                            [
                                'url' => $this->getUrl(
                                    '*/*/index',
                                    ['store' => $storeId, 'customer' => $customerId]
                                ),
                            ]
                        )
                    );
                } else {
                    $this->_redirect('checkout/*/index', ['store' => $storeId, 'customer' => $customerId]);
                }
                $this->_redirectFlag = true;
                return $this;
            } else {
                throw new InputException(__("This store couldn't be found. Verify the store and try again."));
            }
        } else {
            // try to find quote for selected store
            $cart->setStoreId($storeId);
        }

        $quote = $cart->getQuote();
        /**
         * @var \Magento\Store\Model\Store $store
         */
        $store = $storeManager->getStore($storeId);
        // Currency init
        if ($quote->getId()) {
            if ($quote->getQuoteCurrencyCode() != $store->getCurrentCurrencyCode()) {
                $this->setForcedCurrencyFromQuoteToStore($quote, $store);
            }
        } else {
            // customer and addresses should be set to resolve situation when no quote was saved for customer previously
            // otherwise quote would be saved with customer_id = null and zero totals
            $this->setCustomerModelToQuota($customer, $quote);
            $quote->getBillingAddress();
            $quote->getShippingAddress();
            $quote->setStore($store);
            $this->_objectManager->get(\Magento\Quote\Api\CartRepositoryInterface::class)->save($quote);
        }

        $this->_registry->register('checkout_current_quote', $quote);
        $this->_registry->register('checkout_current_customer', $customer);
        $this->_registry->register('checkout_current_store', $storeManager->getStore($storeId));

        return $this;
    }

    /**
     * Renderer for page title
     *
     * @return $this
     */
    protected function _initTitle()
    {
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__('Customers'));
        $title->prepend(__('Customers'));
        $customer = $this->_registry->registry('checkout_current_customer');
        if ($customer) {
            $title->prepend($customer->getName());
        }
        $itemsBlock = $this->_view->getLayout()->getBlock('ID');
        if (is_object($itemsBlock) && is_callable([$itemsBlock, 'getHeaderText'])) {
            $title->prepend($itemsBlock->getHeaderText());
        } else {
            $title->prepend(__('Shopping Cart'));
        }
        return $this;
    }

    /**
     * Process exceptions in ajax requests
     *
     * @param \Exception $e
     * @return void
     */
    protected function _processException(\Exception $e)
    {
        if ($e instanceof LocalizedException) {
            $result = ['error' => $e->getMessage()];
        } elseif ($e instanceof \Exception) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $result = ['error' => __('An error occurred. For details, see the error log.')];
        }
        $this->getResponse()->representJson($this->_objectManager->get(
            \Magento\Framework\Json\Helper\Data::class
        )->jsonEncode($result));
    }

    /**
     * Acl check for quote modifications
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _isModificationAllowed()
    {
        if (!$this->_authorization->isAllowed('Magento_AdvancedCheckout::update')) {
            throw new LocalizedException(__('You do not have access to this.'));
        }
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'Magento_AdvancedCheckout::view'
        ) || $this->_authorization->isAllowed(
            'Magento_AdvancedCheckout::update'
        );
    }

    /**
     * Force set currency from Quote to Store
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Store\Model\Store $store
     * @return void
     */
    protected function setForcedCurrencyFromQuoteToStore(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Store\Model\Store $store
    ) {
        /**
         * @var \Magento\Directory\Model\Currency $currency
         */
        $quoteCurrency = $this->_objectManager->create(
            \Magento\Directory\Model\Currency::class
        )->load(
            $quote->getQuoteCurrencyCode()
        );
        $quote->setForcedCurrency($quoteCurrency);

        $store->setCurrentCurrencyCode($quoteCurrency->getCode());
    }

    /**
     * Convert CustomerModel to CustomerDataObject and set it to Quota
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    protected function setCustomerModelToQuota(
        \Magento\Customer\Model\Customer $customer,
        \Magento\Quote\Model\Quote $quote
    ) {
        $customerDataObject = $this->customerFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $customerDataObject,
            $customer->getData(),
            \Magento\Customer\Api\Data\CustomerInterface::class
        );
        $customerDataObject->setId($customer->getId());
        $quote->setCustomer($customerDataObject);
    }
}
