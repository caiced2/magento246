<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\App\ObjectManager;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Balance
     */
    protected $balance;

    /**
     * @var BalanceFactory
     */
    protected $balanceFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var PricingHelper
     */
    private $pricingHelper;

    /**
     * @param CustomerSession $customerSession
     * @param StoreManagerInterface $storeManager
     * @param CheckoutSession $checkoutSession
     * @param BalanceFactory $balanceFactory
     * @param UrlInterface $urlBuilder
     * @param PricingHelper|null $pricingHelper
     */
    public function __construct(
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        BalanceFactory $balanceFactory,
        UrlInterface $urlBuilder,
        PricingHelper $pricingHelper = null
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->balanceFactory = $balanceFactory;
        $this->urlBuilder = $urlBuilder;
        $this->pricingHelper = $pricingHelper ?: ObjectManager::getInstance()->get(PricingHelper::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'customerBalance' => [
                    'isAvailable' => $this->isAvailable(),
                    'amountSubstracted' => $this->getQuote()->getUseCustomerBalance() ? true : false,
                    'usedAmount' => (float)$this->getQuote()->getBaseCustomerBalAmountUsed(),
                    'balance' => (float)$this->getBalance(),
                    'balanceRemoveUrl' => $this->getRemoveUrl(),
                ],
            ]
        ];
        return $config;
    }

    /**
     * Check if customer balance is available
     *
     * @return bool
     */
    protected function isAvailable()
    {
        if (!$this->customerSession->getCustomerId()) {
            return false;
        }
        if (!$this->getBalance()) {
            return false;
        }
        return true;
    }

    /**
     * Get customer balance instance
     *
     * @return Balance
     */
    protected function getBalanceModel()
    {
        if (!$this->balance) {
            $this->balance = $this->balanceFactory->create()
                ->setCustomerId($this->customerSession->getCustomerId())
                ->setWebsiteId($this->storeManager->getStore()->getWebsiteId())
                ->loadByCustomer();
        }
        return $this->balance;
    }

    /**
     * Get balance amount
     *
     * @return float
     */
    protected function getBalance()
    {
        if (!$this->customerSession->getCustomerId()) {
            return 0;
        }

        return $this->pricingHelper->currency($this->getBalanceModel()->getAmount(), false, false);
    }

    /**
     * Retrieve Quote object
     *
     * @return Quote
     */
    protected function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    /**
     * Get controller URL to remove customer balance as payment method
     *
     * @return string
     */
    protected function getRemoveUrl()
    {
        return $this->urlBuilder->getUrl('magento_customerbalance/cart/remove');
    }
}
