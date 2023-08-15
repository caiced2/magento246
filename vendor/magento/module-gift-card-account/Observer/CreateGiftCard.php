<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\ObserverInterface;

class CreateGiftCard implements ObserverInterface
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
     * Create gift card account on event
     * used for event: magento_giftcardaccount_create
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $observer->getEvent()->getRequest();
        $code = $observer->getEvent()->getCode();
        $order = $data->getOrder() ?: ($data->getOrderItem()->getOrder() ?: null);

        $model = $this->giftCAFactory->create()->setStatus(
            \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_ENABLED
        )->setWebsiteId(
            $data->getWebsiteId()
        )->setBalance(
            $data->getAmount()
        )->setLifetime(
            $data->getLifetime()
        )->setIsRedeemable(
            $data->getIsRedeemable()
        )->setOrder(
            $order
        )->save();

        $code->setCode($model->getCode());

        return $this;
    }
}
