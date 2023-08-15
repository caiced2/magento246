<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Observer for return unused gift card amount to the customer balance after order cancelled
 */
class ReturnFundsToStoreCredit implements ObserverInterface
{
    /**
     * Gift card account data
     *
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $giftCAHelper = null;

    /**
     * Customer balance balance
     *
     * @var \Magento\CustomerBalance\Model\Balance
     */
    protected $customerBalance = null;

    /**
     * Store Manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager = null;

    /**
     * @param \Magento\GiftCardAccount\Helper\Data $giftCAHelper
     * @param \Magento\CustomerBalance\Model\Balance $customerBalance
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\GiftCardAccount\Helper\Data $giftCAHelper,
        \Magento\CustomerBalance\Model\Balance $customerBalance,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->giftCAHelper = $giftCAHelper;
        $this->customerBalance = $customerBalance;
        $this->storeManager = $storeManager;
    }

    /**
     * Return funds to store credit
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $cards = $this->giftCAHelper->getCards($order);
        if (is_array($cards)) {
            $balance = 0;
            foreach ($cards as $card) {
                $balance += $card[\Magento\GiftCardAccount\Model\Giftcardaccount::BASE_AMOUNT];
            }

            $totalCardsInvoiced = $order->getGiftCardsInvoiced();
            if ($totalCardsInvoiced) {
                $balance -= $totalCardsInvoiced;
            }

            if ($balance > 0) {
                $this->customerBalance->setCustomerId(
                    $order->getCustomerId()
                )->setWebsiteId(
                    $this->storeManager->getStore($order->getStoreId())->getWebsiteId()
                )->setAmountDelta(
                    $balance
                )->setHistoryAction(
                    \Magento\CustomerBalance\Model\Balance\History::ACTION_REVERTED
                )->setOrder(
                    $order
                )->save();
            }
        }

        return $this;
    }
}
