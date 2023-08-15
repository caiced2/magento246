<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\ObserverInterface;

class ChargeByCode implements ObserverInterface
{
    /**
     * Gift card account giftcardaccount
     *
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory
     */
    protected $giftCAFactory = null;

    /**
     * @param \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
     */
    public function __construct(
        \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
    ) {
        $this->giftCAFactory = $giftCAFactory;
    }

    /**
     * Charge specified Gift Card (using code)
     * used for event: magento_giftcardaccount_charge_by_code
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $id = $observer->getEvent()->getGiftcardaccountCode();
        $amount = $observer->getEvent()->getAmount();

        $this->giftCAFactory->create()->loadByCode(
            $id
        )->charge(
            $amount
        )->setOrder(
            $observer->getEvent()->getOrder()
        )->save();

        return $this;
    }
}
