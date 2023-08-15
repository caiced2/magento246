<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\ObserverInterface;

class RevertGiftCardAccountBalance implements ObserverInterface
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
     * Revert authorized amounts for all order's gift cards
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order) {
            $this->_revertGiftCardsForOrder($order);
        }

        return $this;
    }

    /**
     * Revert authorized amounts for all order's gift cards
     *
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     */
    protected function _revertGiftCardsForOrder(\Magento\Sales\Model\Order $order)
    {
        $cards = $this->giftCAHelper->getCards($order);
        if (is_array($cards)) {
            foreach ($cards as $card) {
                if (isset($card[\Magento\GiftCardAccount\Model\Giftcardaccount::AUTHORIZED])) {
                    $this->_revertById(
                        $card[\Magento\GiftCardAccount\Model\Giftcardaccount::ID],
                        $card[\Magento\GiftCardAccount\Model\Giftcardaccount::AUTHORIZED]
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Revert amount to gift card
     *
     * @param int $id
     * @param float $amount
     * @return $this
     */
    protected function _revertById($id, $amount = 0)
    {
        /** @var \Magento\GiftCardAccount\Model\Giftcardaccount $giftCard */
        $giftCard = $this->giftCAFactory->create()->load($id);

        if ($giftCard) {
            $giftCard->revert($amount)->unsOrder()->save();
        }

        return $this;
    }
}
