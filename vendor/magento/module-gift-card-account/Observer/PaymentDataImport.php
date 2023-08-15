<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\GiftCardAccount\Model\Giftcardaccount;

/**
 * Gift Card Checkout Observer
 */
class PaymentDataImport implements ObserverInterface
{
    /**
     * Gift card account data
     *
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $giftCAHelper = null;

    /**
     * Gift card account giftcardaccount
     *
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory
     */
    protected $giftCAFactory = null;

    /**
     * Store Manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager = null;

    /**
     * @param \Magento\GiftCardAccount\Helper\Data $giftCAHelper
     * @param \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\GiftCardAccount\Helper\Data $giftCAHelper,
        \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->giftCAHelper = $giftCAHelper;
        $this->giftCAFactory = $giftCAFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Set flag that giftcard applied on payment step in checkout process
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $quote \Magento\Quote\Model\Quote */
        $quote = $observer->getEvent()->getPayment()->getQuote();
        if (!$quote || !$quote->getCustomerId()) {
            return $this;
        }

        $this->removeInapplicableGiftCards($quote);

        /* Gift cards validation */
        $cards = $this->giftCAHelper->getCards($quote);
        $website = $this->storeManager->getStore($quote->getStoreId())->getWebsite();
        foreach ($cards as $one) {
            $this->giftCAFactory->create()
                ->loadByCode($one[Giftcardaccount::CODE])
                ->isValid(true, true, $website);
        }

        if ((double)$quote->getBaseGiftCardsAmountUsed()) {
            $quote->setGiftCardAccountApplied(true);
            $input = $observer->getEvent()->getInput();
            if (!$input->getMethod()) {
                $input->setMethod('free');
            }
        }
        return $this;
    }

    /**
     * Remove used and expired Gift Cards from current quote
     *
     * This method checks the current Quote to find used and expired Gift Cards (Gift Cards with amount of balance = 0)
     * and removes them from quote. This is required to avoid error message 'Please correct the gift card code.'
     * during order creation and to prevent blocking of order creation by using Gift Cards that was already applied
     * and have zero balance or expired ones.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    private function removeInapplicableGiftCards(\Magento\Quote\Model\Quote $quote)
    {
        $giftCardsList = $this->giftCAHelper->getCards($quote);
        foreach ($giftCardsList as $giftCardItem) {
            /** @var Giftcardaccount $giftCard */
            $giftCard = $this->giftCAFactory->create()
                ->loadByCode($giftCardItem[Giftcardaccount::CODE]);
            $state = $giftCard->getState();
            if ($state == Giftcardaccount::STATE_USED
                || $state == Giftcardaccount::STATE_EXPIRED
                || $state == Giftcardaccount::STATE_REDEEMED) {
                $giftCard->removeFromCart(true, $quote);
            }
        }
    }
}
