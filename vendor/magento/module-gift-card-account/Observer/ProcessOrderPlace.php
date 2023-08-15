<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\ObserverInterface;

use \Magento\GiftCardAccount\Model\Giftcardaccount;

class ProcessOrderPlace implements ObserverInterface
{
    /**
     * Gift card account data
     *
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $giftCAHelper;

    /**
     * Gift card account giftcardaccount
     *
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory
     */
    protected $giftCAFactory;

    /**
     * @param \Magento\GiftCardAccount\Helper\Data $giftCAHelper
     * @param \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
     */
    public function __construct(
        \Magento\GiftCardAccount\Helper\Data $giftCAHelper,
        \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
    ) {
        $this->giftCAHelper = $giftCAHelper;
        $this->giftCAFactory = $giftCAFactory;
    }

    /**
     * Charge all gift cards applied to the order
     * used for event: sales_order_place_after
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        /** @var \Magento\Quote\Model\Quote\Address $address */
        $address = $observer->getEvent()->getAddress();
        if (!$address) {
            // Single address checkout.
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getEvent()->getQuote();
            $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        }

        $order->setGiftCards($address->getGiftCards());
        $order->setGiftCardsAmount($address->getGiftCardsAmount());
        $order->setBaseGiftCardsAmount($address->getBaseGiftCardsAmount());
        $cards = $this->giftCAHelper->getCards($order);
        if (is_array($cards)) {
            foreach ($cards as &$card) {
                $this->giftCAFactory->create()
                    ->load($card[Giftcardaccount::ID])
                    ->charge($card[Giftcardaccount::BASE_AMOUNT])
                    ->setOrder($order)
                    ->save();
                $card[Giftcardaccount::AUTHORIZED] = $card[Giftcardaccount::BASE_AMOUNT];
            }
            $this->giftCAHelper->setCards($order, $cards);
        }

        return $this;
    }
}
