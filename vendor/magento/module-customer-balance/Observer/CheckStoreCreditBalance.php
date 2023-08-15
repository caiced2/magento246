<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

/**
 * Customer balance observer
 */
class CheckStoreCreditBalance
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * @var \Magento\Checkout\Model\Type\Onepage
     */
    protected $_onePageCheckout;

    /**
     * Constructor
     *
     * @param \Magento\Checkout\Model\Type\Onepage $onePageCheckout
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Checkout\Model\Type\Onepage $onePageCheckout,
        \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_onePageCheckout = $onePageCheckout;
        $this->_balanceFactory = $balanceFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * Check store credit balance
     *
     * @param   \Magento\Sales\Model\Order $order
     * @throws  \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function execute(\Magento\Sales\Model\Order $order)
    {
        if ($order->getBaseCustomerBalanceAmount() > 0) {
            $websiteId = $this->_storeManager->getStore($order->getStoreId())->getWebsiteId();

            $balance = $this->_balanceFactory->create()->setCustomerId(
                $order->getCustomerId()
            )->setWebsiteId(
                $websiteId
            )->loadByCustomer()->getAmount();

            if ($order->getBaseCustomerBalanceAmount() - $balance >= 0.0001) {
                $this->_onePageCheckout->getCheckout()->setUpdateSection(
                    'payment-method'
                )->setGotoSection(
                    'payment'
                );

                throw new \Magento\Framework\Exception\LocalizedException(
                    __('You do not have enough store credit to complete this order.')
                );
            }
        }
    }
}
