<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

/**
 * Customer balance observer
 */
class RevertStoreCreditForOrder
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
     * Constructor
     *
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_balanceFactory = $balanceFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * Revert authorized store credit amount for order
     *
     * @param   \Magento\Sales\Model\Order $order
     * @return  $this
     */
    public function execute(\Magento\Sales\Model\Order $order)
    {
        if (!$order->getCustomerId() || !$order->getBaseCustomerBalanceAmount()) {
            return $this;
        }

        $customerAmountDelta = $order->getBaseCustomerBalanceAmount() - $order->getBaseCustomerBalanceInvoiced();

        $this->_balanceFactory->create()->setCustomerId(
            $order->getCustomerId()
        )->setWebsiteId(
            $this->_storeManager->getStore($order->getStoreId())->getWebsiteId()
        )->setAmountDelta(
            $customerAmountDelta
        )->setHistoryAction(
            \Magento\CustomerBalance\Model\Balance\History::ACTION_REVERTED
        )->setOrder(
            $order
        )->save();

        return $this;
    }
}
